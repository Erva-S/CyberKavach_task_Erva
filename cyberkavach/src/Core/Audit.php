<?php
namespace CyberKavach\Core;

class Audit
{
    public static function log(string $action, string $module, ?string $recordType = null, ?int $recordId = null, $old = null, $new = null): void
    {
        $data = [
            'ts' => date('c'),
            'action' => $action,
            'module' => $module,
            'record_type' => $recordType,
            'record_id' => $recordId,
            'old' => $old,
            'new' => $new,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ];

        $line = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        file_put_contents(__DIR__ . '/../../storage/logs/audit.log', $line, FILE_APPEND | LOCK_EX);
    }
}
