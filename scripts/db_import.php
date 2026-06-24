<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
use CyberKavach\Core\Environment;

$base = dirname(__DIR__);
Environment::load($base . '/.env');
$dbConfig = require $base . '/config/database.php';

$host = $dbConfig['host'] ?? '127.0.0.1';
$port = $dbConfig['port'] ?? '3306';
$user = $dbConfig['username'] ?? 'root';
$pass = $dbConfig['password'] ?? '';

$dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) {
    echo "Failed to connect to MySQL: " . $e->getMessage() . "\n";
    exit(1);
}

$schemaFile = $base . '/database/schema.sql';
if (!file_exists($schemaFile)) {
    echo "Schema file not found: $schemaFile\n";
    exit(1);
}

$sql = file_get_contents($schemaFile);
// Split statements by ; followed by newline to be safe
$parts = preg_split('/;\s*\n/', $sql);
foreach ($parts as $part) {
    $s = trim($part);
    if ($s === '') continue;
    try {
        $pdo->exec($s);
    } catch (Throwable $e) {
        // show error but continue
        echo "Warning: ", $e->getMessage(), "\n";
    }
}

echo "Schema import attempted. Check database 'cyberkavach' exists.\n";
exit(0);
