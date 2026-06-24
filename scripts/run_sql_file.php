<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { echo "CLI only\n"; exit(1); }
require_once dirname(__DIR__) . '/vendor/autoload.php';
use CyberKavach\Core\Environment;

$path = $argv[1] ?? null;
if (!$path) { echo "Usage: php scripts/run_sql_file.php <path-to-sql>\n"; exit(1); }
$base = dirname(__DIR__);
Environment::load($base . '/.env');
$dbConfig = require $base . '/config/database.php';
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConfig['host'], $dbConfig['port'], $dbConfig['database']);
try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) {
    echo "DB connect error: " . $e->getMessage() . "\n";
    exit(1);
}
$file = $base . '/' . ltrim($path, '/\\');
if (!file_exists($file)) { echo "File not found: $file\n"; exit(1); }
$sql = file_get_contents($file);
$parts = preg_split('/;\s*\n/', $sql);
foreach ($parts as $part) {
    $s = trim($part);
    if ($s === '') continue;
    try { $pdo->exec($s); } catch (Throwable $e) { echo "Warning: " . $e->getMessage() . "\n"; }
}

echo "Executed SQL file: $file\n";
exit(0);
