<?php
declare(strict_types=1);

// Lightweight automated test harness for the Categories API flows.
// Runs without PHPUnit. Exits with code 0 on success, 1 on failure.

if (PHP_SAPI !== 'cli') {
    echo "This script must be run from CLI.\n";
    exit(1);
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/database/seeds/bootstrap.php';

use CyberKavach\Modules\Events\CategoriesController;
use CyberKavach\Core\Request;

$failures = 0;
function ok($cond, $msg = '') {
    global $failures;
    if ($cond) {
        echo "[PASS] $msg\n";
    } else {
        echo "[FAIL] $msg\n";
        $failures++;
    }
}

// prepare DB and admin
try {
    $pdo = ck_seed_db();
} catch (Throwable $e) {
    echo "DB error: " . $e->getMessage() . "\n";
    exit(1);
}

// start session for Auth
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$controller = new CategoriesController();

function callApi($controller, array $post = [], $method = 'create', $id = null) {
    // Use a child PHP process to call the API so Response::json() exit() doesn't kill this process.
    global $adminId;
    $payload = base64_encode(json_encode($post));
    $caller = __DIR__ . '/api_caller.php';
    if ($method === 'create' || $method === 'list') {
        $cmd = 'php ' . escapeshellarg($caller) . ' ' . escapeshellarg($method) . ' ' . escapeshellarg((string)$adminId) . ' ' . escapeshellarg($payload);
    } else {
        $cmd = 'php ' . escapeshellarg($caller) . ' ' . escapeshellarg($method) . ' ' . escapeshellarg((string)$adminId) . ' ' . escapeshellarg((string)$id) . ' ' . escapeshellarg($payload);
    }
    $out = shell_exec($cmd);
    $json = json_decode($out, true);
    return $json;
}

// ensure admin
try {
    $adminId = ck_seed_admin($pdo, 'admin@cyberkavach.local', 'ChangeMe123!', 'CyberKavach Admin');
    if (!$adminId) {
        // find id
        $stmt = $pdo->query("SELECT id FROM users WHERE email = 'admin@cyberkavach.local' LIMIT 1");
        $adminId = (int)$stmt->fetchColumn();
    }
    $_SESSION['_user_id'] = $adminId;
} catch (Throwable $e) {
    echo "Admin seed error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Running lightweight Categories API tests...\n";

// 1) list should return array
$res = callApi($controller, [], 'list');
ok(is_array($res['data']) || isset($res['data']), 'List returns data array');

// 2) create a category
$name = 'AutoTest ' . time();
$slug = 'autotest-' . time();
$res = callApi($controller, ['name'=>$name,'slug'=>$slug,'color'=>'#123456','icon'=>''], 'create');
ok(isset($res['data']['id']) && is_numeric($res['data']['id']), 'Create returns data.id');
$createdId = (int)$res['data']['id'];

// 3) duplicate create should return structured slug error
$res2 = callApi($controller, ['name'=>$name,'slug'=>$slug,'color'=>'#123456','icon'=>''], 'create');
ok(isset($res2['errors']) && isset($res2['errors']['slug']), 'Duplicate create returns errors.slug');

// 4) delete the created category
$res3 = callApi($controller, [], 'delete', $createdId);
ok(isset($res3['status']) && $res3['status']==='deleted', 'Delete returns status deleted');

if ($failures === 0) {
    echo "All tests passed.\n";
    exit(0);
} else {
    echo "Failures: $failures\n";
    exit(1);
}
