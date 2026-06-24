<?php
declare(strict_types=1);

use CyberKavach\Core\Environment;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$base = dirname(__DIR__, 2);
Environment::load($base . '/.env');

function ck_seed_db(): PDO
{
    $base = dirname(__DIR__, 2);
   $dbConfig = require $base . '/config/database.php';
    
    $dbConfig['host'] = 'localhost'; // <-- Force XAMPP to accept the connection

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConfig['host'], $dbConfig['port'], $dbConfig['database']);
    return new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function ck_generate_ulid(): string
{
    $time = (int) floor(microtime(true) * 1000);
    $random = bin2hex(random_bytes(10));
    return substr(str_pad((string) $time, 12, '0', STR_PAD_LEFT) . $random, 0, 26);
}

function ck_assign_role(PDO $pdo, int $userId, string $slug): bool
{
    $stmt = $pdo->prepare('SELECT id FROM roles WHERE slug = :slug LIMIT 1');
    $stmt->execute([':slug' => $slug]);
    $roleId = $stmt->fetchColumn();
    if (!$roleId) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT id FROM user_roles WHERE user_id = :uid AND role_id = :rid LIMIT 1');
    $stmt->execute([':uid' => $userId, ':rid' => $roleId]);
    if ($stmt->fetchColumn()) {
        return true;
    }

    $stmt = $pdo->prepare('INSERT INTO user_roles (user_id, role_id, assigned_by, assigned_at, is_active) VALUES (:uid, :rid, NULL, NOW(), 1)');
    $stmt->execute([':uid' => $userId, ':rid' => $roleId]);
    return true;
}

function ck_seed_admin(PDO $pdo, string $email, string $password, string $name): void
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        echo "User already exists with id: {$existing}\n";
        if (ck_assign_role($pdo, (int) $existing, 'faculty_coordinator')) {
            echo "Ensured faculty_coordinator role assignment.\n";
        }
        return;
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare('INSERT INTO users (ulid, name, email, password_hash, email_verified_at, created_at, updated_at) VALUES (:ulid, :name, :email, :password_hash, NOW(), NOW(), NOW())');
    $stmt->execute([
        ':ulid' => ck_generate_ulid(),
        ':name' => $name,
        ':email' => $email,
        ':password_hash' => $passwordHash,
    ]);

    $userId = (int) $pdo->lastInsertId();
    echo "Created user id: {$userId}\n";

    if (ck_assign_role($pdo, $userId, 'faculty_coordinator')) {
        echo "Assigned faculty_coordinator role.\n";
    } else {
        echo "faculty_coordinator role not found. Run the role seed first.\n";
    }
}