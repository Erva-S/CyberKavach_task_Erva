<?php
declare(strict_types=1);

// Simple CLI seeder runner for CyberKavach
if (PHP_SAPI !== 'cli') {
    echo "This script must be run from CLI.\n";
    exit(1);
}

try {
    require __DIR__ . '/bootstrap.php';
    $pdo = ck_seed_db();
} catch (Throwable $e) {
    echo "DB connection error: " . $e->getMessage() . "\n";
    exit(1);
}

$seedFile = __DIR__ . '/seed_roles_permissions.sql';
if (!file_exists($seedFile)) {
    echo "Seed file not found: $seedFile\n";
    exit(1);
}

$sql = file_get_contents($seedFile);
if ($sql === false) {
    echo "Failed to read seed file.\n";
    exit(1);
}

echo "Running seed: $seedFile\n";

// Split statements by semicolon - simple approach suitable for idempotent seeds
$stmts = array_filter(array_map('trim', explode(";", $sql)));

try {
    $pdo->beginTransaction();

    foreach ($stmts as $stmt) {
        if ($stmt === '' || str_starts_with(ltrim($stmt), '--')) {
            continue;
        }

        try {
            $pdo->exec($stmt);
        } catch (Throwable $e) {
            echo "Statement failed: " . $e->getMessage() . "\n";
        }
    }

    $adminEmail = getenv('SEED_ADMIN_EMAIL') ?: 'admin@cyberkavach.local';
    $adminPassword = getenv('SEED_ADMIN_PASSWORD') ?: 'ChangeMe123!';
    $adminName = getenv('SEED_ADMIN_NAME') ?: 'CyberKavach Admin';
    ck_seed_admin($pdo, $adminEmail, $adminPassword, $adminName);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Seeding completed.\n";

exit(0);
