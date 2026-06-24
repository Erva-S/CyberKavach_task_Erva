<?php
namespace CyberKavach\Modules\Approvals;

use CyberKavach\Core\Request;
use CyberKavach\Core\Response;
use CyberKavach\Core\Auth;
use CyberKavach\Core\RBAC;
use CyberKavach\Core\Audit;

class ApprovalsController
{
    private ApprovalService $svc;
    private Auth $auth;
    private RBAC $rbac;

    public function __construct()
    {
        $this->svc = new ApprovalService();
        $this->auth = new Auth();
        $this->rbac = new RBAC();
    }

    public function create(Request $request): void
    {
        if (!$this->auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }

        $user = $this->auth->user();
        $data = $request->json() ?? $request->all();

        $payload = [
            'type' => $data['type'] ?? 'generic',
            'title' => $data['title'] ?? ($data['type'] ?? 'Request'),
            'description' => $data['description'] ?? null,
            'requested_by' => $user['id'],
            'levels' => $data['levels'] ?? 3,
            'sla_hours' => $data['sla_hours'] ?? 24,
            'first_approver_role' => $data['first_approver_role'] ?? 'tech_coordinator',
            'related_id' => $data['related_id'] ?? null,
            'related_type' => $data['related_type'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ];

        try {
            $id = $this->svc->createRequest($payload);
            Response::json(['status' => 'created', 'id' => $id], 201);
        } catch (\Throwable $e) {
            Response::json(['error' => 'Failed to create request', 'message' => $e->getMessage()], 500);
        }
    }

    public function get(Request $request, string $id): void
    {
        if (!$this->auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }

        $db = \CyberKavach\Core\Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM approval_requests WHERE ulid = :ulid LIMIT 1');
        $stmt->execute([':ulid' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            Response::json(['error' => 'Not found'], 404);
        }
        Response::json(['request' => $row]);
    }

    public function timeline(Request $request, string $id): void
    {
        if (!$this->auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }

        // find numeric id
        $db = \CyberKavach\Core\Database::getConnection();
        $stmt = $db->prepare('SELECT id FROM approval_requests WHERE ulid = :ulid LIMIT 1');
        $stmt->execute([':ulid' => $id]);
        $rid = $stmt->fetchColumn();
        if (!$rid) {
            Response::json(['error' => 'Not found'], 404);
        }
        $timeline = $this->svc->getTimeline((int)$rid);
        Response::json(['timeline' => $timeline]);
    }

    public function list(Request $request): void
    {
        if (!$this->auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }

        $user = $this->auth->user();
        $db = \CyberKavach\Core\Database::getConnection();

        // if user can view_all approvals, return full list, otherwise only own
        $canViewAll = $this->rbac->hasPermission((int)$user['id'], 'approvals', 'view_all');
        if ($canViewAll) {
            $stmt = $db->query('SELECT * FROM approval_requests ORDER BY created_at DESC LIMIT 200');
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $stmt = $db->prepare('SELECT * FROM approval_requests WHERE requested_by = :uid ORDER BY created_at DESC LIMIT 200');
            $stmt->execute([':uid' => $user['id']]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        Response::json(['requests' => $rows]);
    }

    public function action(Request $request, string $requestUlid, string $stepId): void
    {
        if (!$this->auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }
        $user = $this->auth->user();

        // permission check: user must have approve permission
        $canApprove = $this->rbac->hasPermission((int)$user['id'], 'approvals', 'approve');
        if (!$canApprove) {
            Response::json(['error' => 'Forbidden'], 403);
        }

        // resolve numeric request id
        $db = \CyberKavach\Core\Database::getConnection();
        $stmt = $db->prepare('SELECT id FROM approval_requests WHERE ulid = :ulid LIMIT 1');
        $stmt->execute([':ulid' => $requestUlid]);
        $rid = $stmt->fetchColumn();
        if (!$rid) {
            Response::json(['error' => 'Request not found'], 404);
        }

        $data = $request->json() ?? $request->all();
        $action = $data['action'] ?? null; // 'approve' or 'reject'
        $remarks = $data['remarks'] ?? null;

        if (!in_array($action, ['approve', 'reject'], true)) {
            Response::json(['error' => 'Invalid action'], 422);
        }

        try {
            $ok = $this->svc->processStep((int)$rid, (int)$stepId, $action, (int)$user['id'], $remarks);
            if ($ok) {
                Response::json(['status' => 'ok']);
            }
        } catch (\Throwable $e) {
            Response::json(['error' => 'Failed to process step', 'message' => $e->getMessage()], 500);
        }
    }
}
