<?php
namespace CyberKavach\Modules\Events;

use CyberKavach\Core\Request;
use CyberKavach\Core\Response;
use CyberKavach\Core\View;
use CyberKavach\Core\Database;
use CyberKavach\Core\Auth;
use CyberKavach\Core\Audit;
use PDO;

class EventsController
{
    private PDO $db;
    private Auth $auth;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->auth = new Auth();
    }

    public function listPage(Request $request): void
    {
        $stmt = $this->db->prepare('SELECT id, ulid, title, slug, event_start, status FROM events WHERE deleted_at IS NULL ORDER BY event_start DESC LIMIT 50');
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('events/list', [
            'title' => 'Events — CyberKavach',
            'events' => $events,
        ], 'public');
    }

    public function createForm(Request $request): void
    {
        if (!$this->auth->check()) {
            Response::redirect('/login');
        }

        $cats = [];
        try {
            $stmt = $this->db->prepare('SELECT id, name FROM event_categories ORDER BY name ASC');
            $stmt->execute();
            $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // ignore; empty categories allowed
        }

        $error = $request->input('error', null);

        $old = $_SESSION['old_input'] ?? null;
        if (isset($_SESSION['old_input'])) {
            unset($_SESSION['old_input']);
        }

        View::render('events/form', [
            'title' => 'Create event — CyberKavach',
            'categories' => $cats,
            'error' => $error,
            'old' => $old,
        ], 'dashboard');
    }

    public function create(Request $request): void
    {
        if (!$this->auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->json() ?? $request->all();
        $isJson = $request->json() !== null;
        $title = trim((string)($data['title'] ?? ''));
        $slug = trim((string)($data['slug'] ?? '')) ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $description = $data['description'] ?? null;
        $eventStart = isset($data['event_start']) && $data['event_start'] !== '' ? $data['event_start'] : null;
        $eventEnd = isset($data['event_end']) && $data['event_end'] !== '' ? $data['event_end'] : null;
        $capacity = isset($data['capacity']) && $data['capacity'] !== '' ? (int)$data['capacity'] : null;
        $categoryId = isset($data['category_id']) && $data['category_id'] !== '' ? (int)$data['category_id'] : null;

        $errors = [];
        if ($title === '') {
            $errors[] = 'Title required';
        }

        $esSql = null;
        $eeSql = null;
        // normalize datetime-local (e.g. 2026-05-30T14:00) to MySQL datetime
        if ($eventStart) {
            $dt = \DateTime::createFromFormat('Y-m-d\TH:i', $eventStart) ?: \DateTime::createFromFormat('Y-m-d H:i:s', $eventStart) ?: false;
            if ($dt === false) {
                $errors[] = 'Invalid start datetime format';
            } else {
                $esSql = $dt->format('Y-m-d H:i:s');
            }
        }

        if ($eventEnd) {
            $dt2 = \DateTime::createFromFormat('Y-m-d\TH:i', $eventEnd) ?: \DateTime::createFromFormat('Y-m-d H:i:s', $eventEnd) ?: false;
            if ($dt2 === false) {
                $errors[] = 'Invalid end datetime format';
            } else {
                $eeSql = $dt2->format('Y-m-d H:i:s');
            }
        }

        if ($esSql && $eeSql) {
            if (strtotime($eeSql) < strtotime($esSql)) {
                $errors[] = 'End time must be after start time';
            }
        }

        if ($capacity !== null && $capacity < 0) {
            $errors[] = 'Capacity must be zero or positive';
        }

        if ($categoryId !== null) {
            $stmt = $this->db->prepare('SELECT COUNT(1) FROM event_categories WHERE id = :id');
            $stmt->execute([':id' => $categoryId]);
            if ((int)$stmt->fetchColumn() === 0) {
                $errors[] = 'Selected category not found';
            }
        }

        if (!empty($errors)) {
            if ($isJson) {
                Response::json(['errors' => $errors], 422);
            }
            // form submission: save old input and redirect back with first error
            $_SESSION['old_input'] = $data;
            Response::redirect('/events/create?error=' . urlencode($errors[0]));
        }

        $ulid = substr(bin2hex(random_bytes(13)), 0, 26);
        $user = $this->auth->user();
        $createdBy = $user['id'] ?? null;

        $ins = $this->db->prepare('INSERT INTO events (ulid, title, slug, description_html, category_id, capacity, event_start, event_end, created_by, created_at, updated_at) VALUES (:ulid, :title, :slug, :desc, :cat, :cap, :es, :ee, :cb, NOW(), NOW())');
        $ins->execute([
            ':ulid' => $ulid,
            ':title' => $title,
            ':slug' => $slug,
            ':desc' => $description,
            ':cat' => $categoryId,
            ':cap' => $capacity,
            ':es' => $esSql,
            ':ee' => $eeSql,
            ':cb' => $createdBy,
        ]);

        $eventId = (int)$this->db->lastInsertId();
        Audit::log('create', 'events', 'events', (string)$eventId, null, ['title' => $title], $createdBy);

        Response::redirect('/events/' . $ulid);
    }

    public function show(Request $request, string $ulid): void
    {
        $stmt = $this->db->prepare('SELECT * FROM events WHERE ulid = :ulid AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':ulid' => $ulid]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            Response::redirect('/events');
        }

        View::render('events/show', [
            'title' => $event['title'] ?? 'Event',
            'event' => $event,
        ], 'public');
    }
}
