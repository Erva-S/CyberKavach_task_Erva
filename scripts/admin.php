<?php
declare(strict_types=1);

// Admin CLI for role/user management
// Usage examples:
// php scripts/admin.php list-roles
// php scripts/admin.php list-users
// php scripts/admin.php show-user --email=foo@bar
// php scripts/admin.php assign-role --email=foo@bar --role=faculty_coordinator [--by=1]
// php scripts/admin.php revoke-role --email=foo@bar --role=club_member

if (PHP_SAPI !== 'cli') {
    echo "Run from CLI only.\n";
    exit(1);
}

$base = dirname(__DIR__);
require_once $base . '/vendor/autoload.php';

$dbConfig = require $base . '/config/database.php';
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConfig['host'], $dbConfig['port'], $dbConfig['database']);
try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    echo "DB connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

$argvCopy = $argv;
array_shift($argvCopy); // script
$command = $argvCopy[0] ?? 'help';

// parse options like --email=... --role=...
$opts = [];
foreach ($argvCopy as $a) {
    if (str_starts_with($a, '--')) {
        $p = substr($a, 2);
        $parts = explode('=', $p, 2);
        $opts[$parts[0]] = $parts[1] ?? true;
    }
}

function listRoles(PDO $pdo)
{
    $stmt = $pdo->query('SELECT id, name, slug, level FROM roles ORDER BY level DESC, name ASC');
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        printf("%3d | %s (%s) | level=%d\n", $r['id'], $r['name'], $r['slug'], $r['level']);
    }
}

function listUsers(PDO $pdo)
{
    $stmt = $pdo->query('SELECT id, ulid, name, email, status FROM users ORDER BY id DESC LIMIT 200');
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        printf("%5d | %s | %s | %s\n", $r['id'], $r['email'], $r['name'], $r['status']);
    }
}

function showUser(PDO $pdo, string $email)
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $u = $stmt->fetch();
    if (!$u) {
        echo "User not found: $email\n";
        return;
    }
    print_r($u);
    $stmt = $pdo->prepare('SELECT r.* FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = :uid');
    $stmt->execute([':uid' => $u['id']]);
    $roles = $stmt->fetchAll();
    echo "Roles:\n";
    foreach ($roles as $r) {
        printf(" - %s (%s)\n", $r['name'], $r['slug']);
    }
}

function assignRole(PDO $pdo, string $email, string $roleSlug, ?int $assignedBy = null)
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $uid = $stmt->fetchColumn();
        if (!$uid) {
            throw new RuntimeException('User not found: ' . $email);
        }

        $stmt = $pdo->prepare('SELECT id FROM roles WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $roleSlug]);
        $rid = $stmt->fetchColumn();
        if (!$rid) {
            throw new RuntimeException('Role not found: ' . $roleSlug);
        }

        $stmt = $pdo->prepare('SELECT id FROM user_roles WHERE user_id = :uid AND role_id = :rid LIMIT 1');
        $stmt->execute([':uid' => $uid, ':rid' => $rid]);
        if ($stmt->fetchColumn()) {
            echo "User already has role $roleSlug\n";
            $pdo->commit();
            return;
        }

        $ins = $pdo->prepare('INSERT INTO user_roles (user_id, role_id, assigned_by, assigned_at, is_active) VALUES (:uid, :rid, :ab, NOW(), 1)');
        $ins->execute([':uid' => $uid, ':rid' => $rid, ':ab' => $assignedBy]);
        $pdo->commit();
        echo "Assigned role $roleSlug to $email\n";
        // Audit log
        try {
            \CyberKavach\Core\Audit::log('role.assign', 'users', 'user_roles', null, null, ['user_id' => $uid, 'role_id' => $rid], $assignedBy);
        } catch (Throwable $e) {
            // non-fatal: log to stdout
            echo "Audit log failed: " . $e->getMessage() . "\n";
        }
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage() . "\n";
    }
}

function revokeRole(PDO $pdo, string $email, string $roleSlug)
{
    try {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $uid = $stmt->fetchColumn();
        if (!$uid) {
            throw new RuntimeException('User not found: ' . $email);
        }
        $stmt = $pdo->prepare('SELECT id FROM roles WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $roleSlug]);
        $rid = $stmt->fetchColumn();
        if (!$rid) {
            throw new RuntimeException('Role not found: ' . $roleSlug);
        }

        $del = $pdo->prepare('DELETE FROM user_roles WHERE user_id = :uid AND role_id = :rid');
        $del->execute([':uid' => $uid, ':rid' => $rid]);
        echo "Revoked role $roleSlug from $email\n";
        try {
            \CyberKavach\Core\Audit::log('role.revoke', 'users', 'user_roles', null, null, ['user_id' => $uid, 'role_id' => $rid], null);
        } catch (Throwable $e) {
            echo "Audit log failed: " . $e->getMessage() . "\n";
        }
    } catch (Throwable $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

switch ($command) {
    case 'help':
        echo "Admin CLI\n";
        echo "Commands: list-roles, list-users, show-user, assign-role, revoke-role\n";
        echo "See script header for usage examples.\n";
        break;
    case 'list-roles':
        listRoles($pdo);
        break;
    case 'list-users':
        listUsers($pdo);
        break;
    case 'show-user':
        $email = $opts['email'] ?? null;
        if (!$email) { echo "--email required\n"; break; }
        showUser($pdo, $email);
        break;
    case 'assign-role':
        $email = $opts['email'] ?? null;
        $role = $opts['role'] ?? null;
        $by = isset($opts['by']) ? (int)$opts['by'] : null;
        if (!$email || !$role) { echo "--email and --role required\n"; break; }
        assignRole($pdo, $email, $role, $by);
        break;
    case 'revoke-role':
        $email = $opts['email'] ?? null;
        $role = $opts['role'] ?? null;
        if (!$email || !$role) { echo "--email and --role required\n"; break; }
        revokeRole($pdo, $email, $role);
        break;
    default:
        echo "Unknown command: $command\n";
        break;
}

exit(0);
