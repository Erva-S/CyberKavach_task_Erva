<?php
namespace CyberKavach\Modules\Notifications;

use CyberKavach\Core\Mailer;
use CyberKavach\Core\Database;
use CyberKavach\Core\Audit;
use PDO;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class NotificationService
{
    private PDO $db;
    private Mailer $mailer;
    private ?Environment $twig = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->mailer = new Mailer();
        // try to initialise Twig if available
        try {
            $loader = new FilesystemLoader(__DIR__ . '/../../../views/emails');
            $appConfig = @include __DIR__ . '/../../../../config/app.php';
            $isDev = true;
            if (is_array($appConfig) && array_key_exists('env', $appConfig)) {
                $isDev = ($appConfig['env'] === 'development' || $appConfig['debug'] === true);
            }
            $cacheDir = __DIR__ . '/../../../storage/cache/twig';
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            $this->twig = new Environment($loader, ['cache' => $isDev ? false : $cacheDir]);
        } catch (\Throwable $e) {
            $this->twig = null;
        }
    }

    /**
     * Render an email template from views/emails with simple {{placeholders}}
     */
    public function renderTemplate(string $templateName, array $data = []): string
    {
        // Prefer Twig rendering when available
        if ($this->twig !== null) {
            $tpl = $templateName . '.twig';
            try {
                return $this->twig->render($tpl, $data);
            } catch (\Throwable $e) {
                // fallback to basic renderer below
            }
        }

        $tplFile = __DIR__ . '/../../../views/emails/' . $templateName . '.html';
        if (!file_exists($tplFile)) {
            return '';
        }
        $html = file_get_contents($tplFile);
        foreach ($data as $k => $v) {
            if (is_array($v) || is_object($v)) {
                $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            }
            $safe = htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html = str_replace('{{' . $k . '}}', $safe, $html);
        }
        // remove unreplaced placeholders
        $html = preg_replace('/\{\{[a-zA-Z0-9_]+\}\}/', '', $html);
        return $html;
    }

    /**
     * Notify all users with a given role slug
     */
    public function notifyApproverByRole(string $roleSlug, string $subject, string $htmlBody, array $context = []): int
    {
        $stmt = $this->db->prepare('SELECT u.email, u.name, u.id FROM user_roles ur JOIN roles r ON r.id = ur.role_id JOIN users u ON u.id = ur.user_id WHERE r.slug = :slug AND ur.is_active = 1');
        $stmt->execute([':slug' => $roleSlug]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sent = 0;
        foreach ($rows as $r) {
            // if $htmlBody is empty, try to render a template named by context['template']
            $body = $htmlBody;
            if (empty($body) && !empty($context['template'])) {
                $body = $this->renderTemplate($context['template'], $context['data'] ?? []);
            }
            $ok = $this->mailer->send($r['email'], $r['name'] ?? '', $subject, $body ?: '');
            if ($ok) {
                $sent++;
            }
            // audit
            try {
                Audit::log('email.send', 'notifications', 'email', null, null, ['to' => $r['email'], 'subject' => $subject], (int)$r['id']);
            } catch (\Throwable $e) {
                // ignore audit failure
            }
        }
        return $sent;
    }

    /**
     * Send reminders for approvals with SLA due soon (within $hours)
     */
    public function sendSlaReminders(int $hours = 24): int
    {
        $now = date('Y-m-d H:i:s');
        $threshold = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));
        $stmt = $this->db->prepare('SELECT * FROM approval_requests WHERE status = "pending" AND sla_deadline IS NOT NULL AND sla_deadline <= :threshold AND sla_deadline >= :now');
        $stmt->execute([':threshold' => $threshold, ':now' => $now]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count = 0;
        foreach ($rows as $r) {
            // find current approver role via latest step matching level
            $stmt2 = $this->db->prepare('SELECT approver_role FROM approval_steps WHERE request_id = :rid AND level = :lvl ORDER BY id DESC LIMIT 1');
            $stmt2->execute([':rid' => $r['id'], ':lvl' => $r['current_level']]);
            $role = $stmt2->fetchColumn() ?: 'student_coordinator';

            $subject = "Approval pending: " . ($r['title'] ?? 'Request');
            $body = "<p>Approval request <strong>" . htmlspecialchars($r['title'] ?? '') . "</strong> is due at <strong>" . $r['sla_deadline'] . "</strong>.</p>";
            $sent = $this->notifyApproverByRole($role, $subject, $body, ['request' => $r]);
            if ($sent > 0) $count++;
        }
        return $count;
    }
}
