<?php
namespace CyberKavach\Modules\Events;

use CyberKavach\Core\Request;
use CyberKavach\Core\Response;
use CyberKavach\Core\View;
use CyberKavach\Core\Database;
use CyberKavach\Core\Auth;
use CyberKavach\Core\Audit;
use CyberKavach\Core\RBAC;
use PDO;

class CategoriesController
{
    private PDO $db;
    private Auth $auth;
    private RBAC $rbac;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->auth = new Auth();
        $this->rbac = new RBAC();
    }

    public function listPage(Request $request): void
    {
        if (!$this->auth->check()) {
            Response::redirect('/login');
        }

        $user = $this->auth->user();
        if (!isset($user['id']) || !$this->rbac->hasPermission((int)$user['id'], 'events', 'view')) {
            Response::redirect('/dashboard');
        }

        $stmt = $this->db->prepare('SELECT id, name, slug, color, icon FROM event_categories ORDER BY name ASC');
        $stmt->execute();
        $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('events/categories/list', [
            'title' => 'Event categories — CyberKavach',
            'categories' => $cats,
        ], 'dashboard');
    }

    public function createForm(Request $request): void
    {
        if (!$this->auth->check()) {
            Response::redirect('/login');
        }

        $user = $this->auth->user();
        if (!isset($user['id']) || !$this->rbac->hasPermission((int)$user['id'], 'events', 'create')) {
            Response::redirect('/admin/categories');
        }
        $error = $request->input('error', null);
        $old = $_SESSION['old_input'] ?? null;
        if (isset($_SESSION['old_input'])) {
            unset($_SESSION['old_input']);
        }

        View::render('events/categories/form', [
            'title' => 'Create category — CyberKavach',
            'error' => $error,
            'old' => $old,
        ], 'dashboard');
    }

    public function create(Request $request): void
    {
        if (!$this->auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }

        $user = $this->auth->user();
        if (!isset($user['id']) || !$this->rbac->hasPermission((int)$user['id'], 'events', 'create')) {
            Response::json(['error' => 'Forbidden'], 403);
        }

        $data = $request->json() ?? $request->all();
        $name = trim((string)($data['name'] ?? ''));
        $slug = trim((string)($data['slug'] ?? '')) ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $color = trim((string)($data['color'] ?? '')) ?: null;
        $icon = trim((string)($data['icon'] ?? '')) ?: null;

        $errors = [];
        if ($name === '') {
            $errors[] = 'Name is required';
        }

        // check slug uniqueness
        $stmt = $this->db->prepare('SELECT COUNT(1) FROM event_categories WHERE slug = :slug');
        $stmt->execute([':slug' => $slug]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors[] = 'Slug already in use';
        }

        if (!empty($errors)) {
            $_SESSION['old_input'] = $data;
            Response::redirect('/admin/categories/create?error=' . urlencode($errors[0]));
        }

        $ins = $this->db->prepare('INSERT INTO event_categories (name, slug, color, icon) VALUES (:name, :slug, :color, :icon)');
        $ins->execute([':name' => $name, ':slug' => $slug, ':color' => $color, ':icon' => $icon]);
        $catId = (int)$this->db->lastInsertId();
        $user = $this->auth->user();
        Audit::log('create', 'event_categories', 'event_categories', (string)$catId, null, ['name' => $name], $user['id'] ?? null);

        Response::redirect('/admin/categories');
    }

    public function editForm(Request $request, $id): void
    {
        if (!$this->auth->check()) {
            Response::redirect('/login');
        }

        $user = $this->auth->user();
        if (!isset($user['id']) || !$this->rbac->hasPermission((int)$user['id'], 'events', 'edit')) {
            Response::redirect('/admin/categories');
        }

        $stmt = $this->db->prepare('SELECT * FROM event_categories WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int)$id]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cat) {
            Response::redirect('/admin/categories');
        }

        $error = $request->input('error', null);
        $old = $_SESSION['old_input'] ?? null;
        if (isset($_SESSION['old_input'])) {
            unset($_SESSION['old_input']);
        }

        View::render('events/categories/edit', [
            'title' => 'Edit category — CyberKavach',
            'category' => $cat,
            'error' => $error,
            'old' => $old,
        ], 'dashboard');
    }

    public function update(Request $request, $id): void
    {
        if (!$this->auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }

        $user = $this->auth->user();
        if (!isset($user['id']) || !$this->rbac->hasPermission((int)$user['id'], 'events', 'edit')) {
            Response::json(['error' => 'Forbidden'], 403);
        }

        $data = $request->json() ?? $request->all();
        $name = trim((string)($data['name'] ?? ''));
        $slug = trim((string)($data['slug'] ?? '')) ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $color = trim((string)($data['color'] ?? '')) ?: null;
        $icon = trim((string)($data['icon'] ?? '')) ?: null;

        $errors = [];
        if ($name === '') {
            $errors[] = 'Name is required';
        }

        $stmt = $this->db->prepare('SELECT COUNT(1) FROM event_categories WHERE slug = :slug AND id != :id');
        $stmt->execute([':slug' => $slug, ':id' => (int)$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors[] = 'Slug already in use';
        }

        if (!empty($errors)) {
            $_SESSION['old_input'] = $data;
            Response::redirect('/admin/categories/' . (int)$id . '/edit?error=' . urlencode($errors[0]));
        }

        $upd = $this->db->prepare('UPDATE event_categories SET name = :name, slug = :slug, color = :color, icon = :icon WHERE id = :id');
        $upd->execute([':name' => $name, ':slug' => $slug, ':color' => $color, ':icon' => $icon, ':id' => (int)$id]);

        $user = $this->auth->user();
        Audit::log('update', 'event_categories', 'event_categories', (string)$id, null, ['name' => $name], $user['id'] ?? null);

        Response::redirect('/admin/categories');
    }

    public function delete(Request $request, $id): void
    {
        if (!$this->auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }

        $user = $this->auth->user();
        if (!isset($user['id']) || !$this->rbac->hasPermission((int)$user['id'], 'events', 'delete')) {
            Response::json(['error' => 'Forbidden'], 403);
        }

        $stmt = $this->db->prepare('SELECT id, name FROM event_categories WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int)$id]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cat) {
            Response::redirect('/admin/categories');
        }

        $del = $this->db->prepare('DELETE FROM event_categories WHERE id = :id');
        $del->execute([':id' => (int)$id]);
        $user = $this->auth->user();
        Audit::log('delete', 'event_categories', 'event_categories', (string)$id, null, ['name' => $cat['name']], $user['id'] ?? null);

        Response::redirect('/admin/categories');
    }

    // JSON API: List categories
    public function apiList(Request $request): void
    {
        if (!$this->auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }
        $user = $this->auth->user();
        if (!isset($user['id']) || !$this->rbac->hasPermission((int)$user['id'], 'events', 'view')) {
            Response::json(['error' => 'Forbidden'], 403);
        }

        $stmt = $this->db->prepare('SELECT id, name, slug, color, icon FROM event_categories ORDER BY name ASC');
        $stmt->execute();
        $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::json(['data' => $cats]);
    }

    // JSON API: Create category
    public function apiCreate(Request $request): void
    {
        if (!$this->auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }
        $user = $this->auth->user();
        if (!isset($user['id']) || !$this->rbac->hasPermission((int)$user['id'], 'events', 'create')) {
            Response::json(['error' => 'Forbidden'], 403);
        }

        $data = $request->json() ?? $request->all();
        $name = trim((string)($data['name'] ?? ''));
        $slug = trim((string)($data['slug'] ?? '')) ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $color = trim((string)($data['color'] ?? '')) ?: null;
        $icon = trim((string)($data['icon'] ?? '')) ?: null;

        if ($name === '') {
            Response::json(['errors' => ['name' => 'Name is required']], 422);
        }

        $stmt = $this->db->prepare('SELECT COUNT(1) FROM event_categories WHERE slug = :slug');
        $stmt->execute([':slug' => $slug]);
        if ((int)$stmt->fetchColumn() > 0) {
            Response::json(['errors' => ['slug' => 'Slug already in use']], 409);
        }

        $ins = $this->db->prepare('INSERT INTO event_categories (name, slug, color, icon) VALUES (:name, :slug, :color, :icon)');
        $ins->execute([':name' => $name, ':slug' => $slug, ':color' => $color, ':icon' => $icon]);
        $catId = (int)$this->db->lastInsertId();
        Audit::log('create', 'event_categories', 'event_categories', (string)$catId, null, ['name' => $name], $user['id'] ?? null);

        Response::json(['data' => ['id' => $catId, 'name' => $name, 'slug' => $slug, 'color' => $color, 'icon' => $icon]], 201);
    }

    // JSON API: Update category
    public function apiUpdate(Request $request, $id): void
    {
        if (!$this->auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }
        $user = $this->auth->user();
        if (!isset($user['id']) || !$this->rbac->hasPermission((int)$user['id'], 'events', 'edit')) {
            Response::json(['error' => 'Forbidden'], 403);
        }

        $data = $request->json() ?? $request->all();
        $name = trim((string)($data['name'] ?? ''));
        $slug = trim((string)($data['slug'] ?? '')) ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $color = trim((string)($data['color'] ?? '')) ?: null;
        $icon = trim((string)($data['icon'] ?? '')) ?: null;

        if ($name === '') {
            Response::json(['errors' => ['name' => 'Name is required']], 422);
        }

        $stmt = $this->db->prepare('SELECT COUNT(1) FROM event_categories WHERE slug = :slug AND id != :id');
        $stmt->execute([':slug' => $slug, ':id' => (int)$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            Response::json(['errors' => ['slug' => 'Slug already in use']], 409);
        }

        $upd = $this->db->prepare('UPDATE event_categories SET name = :name, slug = :slug, color = :color, icon = :icon WHERE id = :id');
        $upd->execute([':name' => $name, ':slug' => $slug, ':color' => $color, ':icon' => $icon, ':id' => (int)$id]);
        Audit::log('update', 'event_categories', 'event_categories', (string)$id, null, ['name' => $name], $user['id'] ?? null);

        Response::json(['data' => ['id' => (int)$id, 'name' => $name, 'slug' => $slug, 'color' => $color, 'icon' => $icon]]);
    }

    // JSON API: Delete category
    public function apiDelete(Request $request, $id): void
    {
        if (!$this->auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }
        $user = $this->auth->user();
        if (!isset($user['id']) || !$this->rbac->hasPermission((int)$user['id'], 'events', 'delete')) {
            Response::json(['error' => 'Forbidden'], 403);
        }

        $stmt = $this->db->prepare('SELECT id, name FROM event_categories WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int)$id]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cat) {
            Response::json(['error' => 'Not found'], 404);
        }

        $del = $this->db->prepare('DELETE FROM event_categories WHERE id = :id');
        $del->execute([':id' => (int)$id]);
        Audit::log('delete', 'event_categories', 'event_categories', (string)$id, null, ['name' => $cat['name']], $user['id'] ?? null);

        Response::json(['status' => 'deleted']);
    }
}
