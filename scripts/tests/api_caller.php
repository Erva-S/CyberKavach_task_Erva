<?php
declare(strict_types=1);
// Small CLI runner that invokes CategoriesController methods and prints JSON.
if (PHP_SAPI !== 'cli') {
    echo "CLI only\n";
    exit(1);
}
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/database/seeds/bootstrap.php';
use CyberKavach\Modules\Events\CategoriesController;
use CyberKavach\Core\Request;

$method = $argv[1] ?? null;
$adminId = isset($argv[2]) ? (int)$argv[2] : 0;
$id = null;
$payloadB64 = null;
// positional args vary by method: create/list: script.php create <adminId> <payload>
// update/delete: script.php update <adminId> <id> <payload>
if ($method === 'create' || $method === 'list') {
    $payloadB64 = $argv[3] ?? null;
} else {
    $id = isset($argv[3]) ? (int)$argv[3] : null;
    $payloadB64 = $argv[4] ?? null;
}

if (!$method) {
    echo json_encode(['error'=>'missing method']) . PHP_EOL;
    exit(1);
}

if ($payloadB64) {
    $post = json_decode(base64_decode($payloadB64), true) ?: [];
} else {
    $post = [];
}

// start session and set admin
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if ($adminId) $_SESSION['_user_id'] = $adminId;

$controller = new CategoriesController();

// populate POST before building Request so Request->post picks it up
$_GET = [];
$_POST = $post;
// debug prints removed
$req = new Request();

if ($method === 'list') {
    $controller->apiList($req);
} elseif ($method === 'create') {
    $controller->apiCreate($req);
} elseif ($method === 'update') {
    if (!$id) { echo json_encode(['error'=>'missing id']) . PHP_EOL; exit(1); }
    $controller->apiUpdate($req, $id);
} elseif ($method === 'delete') {
    if (!$id) { echo json_encode(['error'=>'missing id']) . PHP_EOL; exit(1); }
    $controller->apiDelete($req, $id);
} else {
    echo json_encode(['error'=>'unknown method']) . PHP_EOL;
    exit(1);
}
