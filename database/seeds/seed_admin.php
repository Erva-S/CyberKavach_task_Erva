<?php
declare(strict_types=1);

// Usage: php seed_admin.php [email] [password] [name]

if (PHP_SAPI !== 'cli') {
    echo "Run from CLI.\n";
    exit(1);
}

$email = $argv[1] ?? 'admin@cyberkavach.local';
$password = $argv[2] ?? 'ChangeMe123!';
$name = $argv[3] ?? 'CyberKavach Admin';

try {
    require __DIR__ . '/bootstrap.php';
    $pdo = ck_seed_db();
} catch (Throwable $e) {
    echo "DB connection error: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    $pdo->beginTransaction();
    ck_seed_admin($pdo, $email, $password, $name);
    $pdo->commit();
    echo "Seeding admin complete.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
