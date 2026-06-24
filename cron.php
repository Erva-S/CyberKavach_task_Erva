<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use CyberKavach\Modules\Approvals\ApprovalService;

if (PHP_SAPI !== 'cli') {
    echo "cron.php must be run from CLI\n";
    exit(1);
}

$cmd = $argv[1] ?? null;
switch ($cmd) {
    case 'approval_sla_check':
        $svc = new ApprovalService();
        $count = $svc->escalate();
        echo "Escalated $count approval(s)\n";
        break;
    case 'email_digest':
        $notifier = new \CyberKavach\Modules\Notifications\NotificationService();
        $sent = $notifier->sendSlaReminders(24);
        echo "Sent $sent SLA reminder(s)\n";
        break;
    default:
        echo "Usage: php cron.php approval_sla_check\n";
        break;
}
