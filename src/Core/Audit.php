<?php
namespace CyberKavach\Core;

use CyberKavach\Core\Database;
use PDO;

class Audit
{
    public static function log(string $action, string $module, ?string $record_type = null, ?string $record_id = null, $old_value = null, $new_value = null, ?int $user_id = null): void
    {
        $db = Database::getConnection();

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $oldJson = $old_value !== null ? json_encode($old_value, JSON_UNESCAPED_UNICODE) : null;
        $newJson = $new_value !== null ? json_encode($new_value, JSON_UNESCAPED_UNICODE) : null;

        $stmt = $db->prepare('INSERT INTO audit_logs (user_id, action, module, record_type, record_id, old_value_json, new_value_json, ip_address, user_agent, created_at) VALUES (:user_id, :action, :module, :record_type, :record_id, :old_json, :new_json, :ip, :ua, NOW())');
        $stmt->execute([
            ':user_id' => $user_id,
            ':action' => $action,
            ':module' => $module,
            ':record_type' => $record_type,
            ':record_id' => $record_id,
            ':old_json' => $oldJson,
            ':new_json' => $newJson,
            ':ip' => $ip,
            ':ua' => $ua
        ]);

        // also write to storage log file for quick debugging
        $logLine = sprintf("%s | user:%s | %s.%s | record:%s/%s | ip:%s | ua:%s\n",
            date('Y-m-d H:i:s'), $user_id ?? 'anon', $module, $action, $record_type ?? '-', $record_id ?? '-', $ip ?? '-', $ua ?? '-'
        );

        @file_put_contents(__DIR__ . '/../../storage/logs/audit.log', $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Returns the most recent audit entries.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recent(int $limit = 50): array
    {
        $db = Database::getConnection();
        $limit = max(1, min($limit, 500));

        $stmt = $db->prepare('SELECT * FROM audit_logs ORDER BY created_at DESC, id DESC LIMIT ' . $limit);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns audit entries for a given module.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forModule(string $module, int $limit = 100): array
    {
        $db = Database::getConnection();
        $limit = max(1, min($limit, 500));

        $stmt = $db->prepare('SELECT * FROM audit_logs WHERE module = :module ORDER BY created_at DESC, id DESC LIMIT ' . $limit);
        $stmt->execute([':module' => $module]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns audit entries for a specific record.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forRecord(string $recordType, string $recordId, int $limit = 100): array
    {
        $db = Database::getConnection();
        $limit = max(1, min($limit, 500));

        $stmt = $db->prepare('SELECT * FROM audit_logs WHERE record_type = :record_type AND record_id = :record_id ORDER BY created_at DESC, id DESC LIMIT ' . $limit);
        $stmt->execute([
            ':record_type' => $recordType,
            ':record_id' => $recordId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
