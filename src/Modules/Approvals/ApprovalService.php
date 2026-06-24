<?php
namespace CyberKavach\Modules\Approvals;

use CyberKavach\Core\Database;
use CyberKavach\Core\Audit;
use CyberKavach\Core\RBAC;
use PDO;
use CyberKavach\Modules\Notifications\NotificationService;

class ApprovalService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->notifier = new NotificationService();
    }

    /**
     * Create a new approval request and first step
     * $data: type, title, description, requested_by, levels (int), sla_hours (per level), related_id, related_type, metadata
     */
    public function createRequest(array $data): int
    {
        $ulid = $this->generateUlid();
        $type = $data['type'] ?? 'generic';
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? null;
        $requestedBy = (int)($data['requested_by'] ?? 0);
        $maxLevel = (int)($data['levels'] ?? 3);
        $priority = $data['priority'] ?? 'normal';
        $relatedId = $data['related_id'] ?? null;
        $relatedType = $data['related_type'] ?? null;
        $metadata = isset($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE) : null;

        // compute SLA deadline for first level
        $slaHours = (int)($data['sla_hours'] ?? 24);
        $slaDeadline = date('Y-m-d H:i:s', strtotime("+{$slaHours} hours"));

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('INSERT INTO approval_requests (ulid, type, title, description, requested_by, current_level, max_level, status, priority, sla_deadline, related_id, related_type, metadata_json, created_at, updated_at) VALUES (:ulid, :type, :title, :desc, :req, 1, :maxl, "pending", :priority, :sla, :rid, :rtype, :meta, NOW(), NOW())');
            $stmt->execute([':ulid' => $ulid, ':type' => $type, ':title' => $title, ':desc' => $description, ':req' => $requestedBy, ':maxl' => $maxLevel, ':priority' => $priority, ':sla' => $slaDeadline, ':rid' => $relatedId, ':rtype' => $relatedType, ':meta' => $metadata]);
            $requestId = (int)$this->db->lastInsertId();

            // insert first step - approver_role left to calling code; placeholder 'coordinator'
            $ins = $this->db->prepare('INSERT INTO approval_steps (request_id, level, approver_role, created_at) VALUES (:rid, 1, :approver_role, NOW())');
            $ins->execute([':rid' => $requestId, ':approver_role' => $data['first_approver_role'] ?? 'tech_coordinator']);


            Audit::log('approval.create', 'approvals', 'approval_requests', (string)$requestId, null, ['type' => $type, 'title' => $title], $requestedBy);

            // notify first approver role using template
            try {
                $role = $data['first_approver_role'] ?? 'tech_coordinator';
                $subject = "New approval request: " . $title;
                $link = (isset($_SERVER['APP_URL']) ? rtrim($_SERVER['APP_URL'], '/') : '') . '/approvals/' . $ulid;
                $html = $this->notifier->renderTemplate('approval_created', [
                    'title' => $title,
                    'ulid' => $ulid,
                    'type' => $type,
                    'description' => $description ?? '',
                    'requested_by_name' => $data['requested_by_name'] ?? '',
                    'created_at' => date('Y-m-d H:i:s'),
                    'request_link' => $link,
                ]);
                $this->notifier->notifyApproverByRole($role, $subject, $html, ['request_id' => $requestId]);
            } catch (\Throwable $e) {
                // non-fatal
            }

            $this->db->commit();
            return $requestId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Process a step action (approve/reject)
     */
    public function processStep(int $requestId, int $stepId, string $action, int $actedBy, ?string $remarks = null): bool
    {
        $this->db->beginTransaction();
        try {
            // update step
            $stmt = $this->db->prepare('UPDATE approval_steps SET action = :action, remarks = :remarks, acted_at = NOW(), assigned_to = :acted_by WHERE id = :id AND request_id = :rid');
            $stmt->execute([':action' => $action, ':remarks' => $remarks, ':acted_by' => $actedBy, ':id' => $stepId, ':rid' => $requestId]);

            // fetch request
            $stmt = $this->db->prepare('SELECT * FROM approval_requests WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $requestId]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$req) {
                throw new \RuntimeException('Approval request not found');
            }

            if ($action === 'approve') {
                $current = (int)$req['current_level'];
                $max = (int)$req['max_level'];
                if ($current >= $max) {
                    // finalize
                    $upd = $this->db->prepare('UPDATE approval_requests SET status = "approved", updated_at = NOW() WHERE id = :id');
                    $upd->execute([':id' => $requestId]);
                    Audit::log('approval.complete', 'approvals', 'approval_requests', (string)$requestId, null, ['status' => 'approved'], $actedBy);
                } else {
                    // advance level
                    $next = $current + 1;
                    // set new SLA deadline: add 48 hours per level by default
                    $slaHours = 48;
                    $newSla = date('Y-m-d H:i:s', strtotime("+{$slaHours} hours"));
                    $upd = $this->db->prepare('UPDATE approval_requests SET current_level = :next, sla_deadline = :sla, updated_at = NOW() WHERE id = :id');
                    $upd->execute([':next' => $next, ':sla' => $newSla, ':id' => $requestId]);

                    // create new step row
                    $ins = $this->db->prepare('INSERT INTO approval_steps (request_id, level, approver_role, created_at) VALUES (:rid, :level, :role, NOW())');
                    $ins->execute([':rid' => $requestId, ':level' => $next, ':role' => 'student_coordinator']);

                    Audit::log('approval.advance', 'approvals', 'approval_requests', (string)$requestId, null, ['next_level' => $next], $actedBy);

                    // notify next approver role using template
                    try {
                        $role = 'student_coordinator';
                        $subject = "Approval advanced: " . $req['title'];
                        $link = (isset($_SERVER['APP_URL']) ? rtrim($_SERVER['APP_URL'], '/') : '') . '/approvals/' . $req['ulid'];
                        $html = $this->notifier->renderTemplate('approval_created', [
                            'title' => $req['title'],
                            'ulid' => $req['ulid'],
                            'type' => $req['type'],
                            'description' => $req['description'] ?? '',
                            'requested_by_name' => '',
                            'created_at' => $req['created_at'],
                            'request_link' => $link,
                        ]);
                        $this->notifier->notifyApproverByRole($role, $subject, $html, ['request_id' => $requestId]);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            } elseif ($action === 'reject') {
                $upd = $this->db->prepare('UPDATE approval_requests SET status = "rejected", updated_at = NOW() WHERE id = :id');
                $upd->execute([':id' => $requestId]);
                Audit::log('approval.reject', 'approvals', 'approval_requests', (string)$requestId, null, ['reason' => $remarks], $actedBy);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Escalate requests that exceeded SLA: increment level or notify
     */
    public function escalate(): int
    {
        $now = date('Y-m-d H:i:s');
        // find overdue pending requests
        $stmt = $this->db->prepare('SELECT * FROM approval_requests WHERE status = "pending" AND sla_deadline IS NOT NULL AND sla_deadline < :now');
        $stmt->execute([':now' => $now]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count = 0;
        foreach ($rows as $r) {
            $requestId = (int)$r['id'];
            $current = (int)$r['current_level'];
            $max = (int)$r['max_level'];
            if ($current < $max) {
                // advance level
                $next = $current + 1;
                $slaHours = 48;
                $newSla = date('Y-m-d H:i:s', strtotime("+{$slaHours} hours"));
                $upd = $this->db->prepare('UPDATE approval_requests SET current_level = :next, sla_deadline = :sla, updated_at = NOW() WHERE id = :id');
                $upd->execute([':next' => $next, ':sla' => $newSla, ':id' => $requestId]);

                $ins = $this->db->prepare('INSERT INTO approval_steps (request_id, level, approver_role, created_at) VALUES (:rid, :level, :role, NOW())');
                $ins->execute([':rid' => $requestId, ':level' => $next, ':role' => 'faculty_coordinator']);

                Audit::log('approval.escalate', 'approvals', 'approval_requests', (string)$requestId, null, ['from' => $current, 'to' => $next], null);

                // notify new approver using template
                try {
                    $role = 'faculty_coordinator';
                    $subject = "Approval escalated: " . $r['title'];
                    $link = (isset($_SERVER['APP_URL']) ? rtrim($_SERVER['APP_URL'], '/') : '') . '/approvals/' . $r['ulid'];
                    $html = $this->notifier->renderTemplate('approval_escalated', [
                        'title' => $r['title'],
                        'ulid' => $r['ulid'],
                        'from' => $current,
                        'to' => $next,
                        'description' => $r['description'] ?? '',
                        'request_link' => $link,
                    ]);
                    $this->notifier->notifyApproverByRole($role, $subject, $html, ['request_id' => $requestId]);
                } catch (\Throwable $e) {
                    // ignore
                }
                $count++;
            } else {
                // already at max level; send SLA warning
                Audit::log('approval.sla_violation', 'approvals', 'approval_requests', (string)$requestId, null, ['level' => $current], null);
            }
        }

        return $count;
    }

    public function getTimeline(int $requestId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM approval_steps WHERE request_id = :rid ORDER BY created_at ASC, id ASC');
        $stmt->execute([':rid' => $requestId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function generateUlid(): string
    {
        return substr(bin2hex(random_bytes(13)), 0, 26);
    }
}
