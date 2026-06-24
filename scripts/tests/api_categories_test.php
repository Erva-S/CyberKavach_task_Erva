<?php
declare(strict_types=1);

// CLI script to exercise category API endpoints.
// Usage:
// php scripts/tests/api_categories_test.php seed                     # seed roles/permissions and admin
// php scripts/tests/api_categories_test.php list                     # list categories
// php scripts/tests/api_categories_test.php create "Name" "slug"   # create category
// php scripts/tests/api_categories_test.php update <id> "Name" "slug"  # update category
// php scripts/tests/api_categories_test.php delete <id>              # delete category

if (PHP_SAPI !== 'cli') {
    echo "This script must be run from CLI.\n";
    exit(1);
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/database/seeds/bootstrap.php';

use CyberKavach\Modules\Events\CategoriesController;
use CyberKavach\Core\Request;

$cmd = $argv[1] ?? 'help';
$arg1 = $argv[2] ?? null;
$arg2 = $argv[3] ?? null;
$arg3 = $argv[4] ?? null;

$pdo = null;
try {
    $pdo = ck_seed_db();
} catch (Throwable $e) {
    echo "DB connection error: " . $e->getMessage() . "\n";
    exit(1);
}

function runSqlFile(PDO $pdo, string $path): void
{
    if (!file_exists($path)) {
        echo "SQL file not found: $path\n";
        return;
    }
    $sql = file_get_contents($path);
    // remove comment lines
    $lines = explode("\n", $sql);
    $clean = [];
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '' || strpos($t, '--') === 0) continue;
        $clean[] = $line;
    }
    $sql = implode("\n", $clean);
    $parts = preg_split('/;\s*\n/', $sql);
    foreach ($parts as $part) {
        $s = trim($part);
        if ($s === '') continue;
        try {
            $pdo->exec($s);
        } catch (Throwable $e) {
            // continue on errors to allow idempotent runs
        }
    }
}

function ensureAdmin(PDO $pdo): int
{
    $email = 'admin@cyberkavach.local';
    ck_seed_admin($pdo, $email, 'ChangeMe123!', 'CyberKavach Admin');
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $id = (int)$stmt->fetchColumn();
    if ($id <= 0) {
        throw new RuntimeException('Failed to create/find admin user');
    }
    return $id;
}

// Start session for Auth to use
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$controller = new CategoriesController();

switch ($cmd) {
    case 'seed':
        echo "Seeding roles & permissions...\n";
        runSqlFile($pdo, dirname(__DIR__) . '/database/seeds/seed_roles_permissions.sql');
        echo "Seeding admin user...\n";
        $id = ensureAdmin($pdo);
        echo "Admin user id: $id\n";
        exit(0);

    case 'list':
        // authenticate as admin
        try {
            $id = ensureAdmin($pdo);
            $_SESSION['_user_id'] = $id;
            // clear POST
            $_GET = [];
            $_POST = [];
            $req = new Request();
            $controller->apiList($req);
        } catch (Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
        break;

    case 'create':
        $name = $arg1 ?? 'Test Category ' . time();
        $slug = $arg2 ?? strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        try {
            $id = ensureAdmin($pdo);
            $_SESSION['_user_id'] = $id;
            $_GET = [];
            $_POST = ['name' => $name, 'slug' => $slug, 'color' => '#007bff', 'icon' => ''];
            $req = new Request();
            $controller->apiCreate($req);
        } catch (Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
        break;

    case 'update':
        $catId = isset($arg1) ? (int)$arg1 : 0;
        if ($catId <= 0) { echo "Missing category id\n"; exit(1); }
        $name = $arg2 ?? 'Updated Category ' . time();
        $slug = $arg3 ?? strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        try {
            $id = ensureAdmin($pdo);
            $_SESSION['_user_id'] = $id;
            $_GET = [];
            $_POST = ['name' => $name, 'slug' => $slug, 'color' => '#00aa00', 'icon' => ''];
            $req = new Request();
            $controller->apiUpdate($req, $catId);
        } catch (Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
        break;

    case 'delete':
        $catId = isset($arg1) ? (int)$arg1 : 0;
        if ($catId <= 0) { echo "Missing category id\n"; exit(1); }
        try {
            $id = ensureAdmin($pdo);
            $_SESSION['_user_id'] = $id;
            $_GET = [];
            $_POST = [];
            $req = new Request();
            $controller->apiDelete($req, $catId);
        } catch (Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
        break;

    default:
        echo "Usage:\n";
        echo " php scripts/tests/api_categories_test.php seed\n";
        echo " php scripts/tests/api_categories_test.php list\n";
        echo " php scripts/tests/api_categories_test.php create \"Name\" \"slug\"\n";
        echo " php scripts/tests/api_categories_test.php update <id> \"Name\" \"slug\"\n";
        echo " php scripts/tests/api_categories_test.php delete <id>\n";
        exit(0);
}

exit(0);
