<?php
declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../vendor/autoload.php';

use CyberKavach\Core\Environment;
use CyberKavach\Core\Request;
use CyberKavach\Core\Router;
use CyberKavach\Core\Response;
use CyberKavach\Core\View;
use CyberKavach\Middleware\CSRFMiddleware;

// Bootstrap
Environment::load(__DIR__ . '/../.env');

$request = new Request();
$router = new Router();

$router->add('GET', '/', function () {
    View::render('public/home', [
        'title' => 'CyberKavach',
        'headline' => 'CyberKavach',
        'lede' => 'Welcome to the CyberKavach scaffold.'
    ]);
});

$router->add('GET', '/events', function (Request $req) {
    $controller = new \CyberKavach\Modules\Events\EventsController();
    $controller->listPage($req);
});

// Admin: categories
$router->add('GET', '/admin/categories', function (Request $req) {
    $controller = new \CyberKavach\Modules\Events\CategoriesController();
    $controller->listPage($req);
});

$router->add('GET', '/admin/categories/create', function (Request $req) {
    $controller = new \CyberKavach\Modules\Events\CategoriesController();
    $controller->createForm($req);
});

$router->add('POST', '/admin/categories/create', function (Request $req) {
    $controller = new \CyberKavach\Modules\Events\CategoriesController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller) {
        $controller->create($r);
    });
});

$router->add('GET', '/admin/categories/{id}/edit', function (Request $req, $id) {
    $controller = new \CyberKavach\Modules\Events\CategoriesController();
    $controller->editForm($req, $id);
});

$router->add('POST', '/admin/categories/{id}/edit', function (Request $req, $id) {
    $controller = new \CyberKavach\Modules\Events\CategoriesController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller, $id) {
        $controller->update($r, $id);
    });
});

$router->add('POST', '/admin/categories/{id}/delete', function (Request $req, $id) {
    $controller = new \CyberKavach\Modules\Events\CategoriesController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller, $id) {
        $controller->delete($r, $id);
    });
});

$router->add('GET', '/events/create', function (Request $req) {
    $controller = new \CyberKavach\Modules\Events\EventsController();
    $controller->createForm($req);
});

$router->add('POST', '/events/create', function (Request $req) {
    $controller = new \CyberKavach\Modules\Events\EventsController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller) {
        $controller->create($r);
    });
});

$router->add('GET', '/events/{ulid}', function (Request $req, $ulid) {
    $controller = new \CyberKavach\Modules\Events\EventsController();
    $controller->show($req, (string)$ulid);
});

$router->add('GET', '/login', function (Request $req) {
    $controller = new \CyberKavach\Modules\Auth\AuthController();
    $controller->loginPage($req);
});

$router->add('POST', '/login', function (Request $req) {
    $controller = new \CyberKavach\Modules\Auth\AuthController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller) {
        $controller->loginForm($r); 
    });
});

$router->add('GET', '/register', function (Request $req) {
    $controller = new \CyberKavach\Modules\Auth\AuthController();
    $controller->registerPage($req);
});

$router->add('GET', '/dashboard', function (Request $req) {
    $controller = new \CyberKavach\Modules\Auth\AuthController();
    $controller->dashboard($req);
});

$router->add('GET', '/dashboard/{role}', function (Request $req, $role) {
    $controller = new \CyberKavach\Modules\Auth\AuthController();
    $controller->dashboard($req, (string)$role);
});

$router->add('POST', '/api/auth/login', function (Request $req) {
    $controller = new \CyberKavach\Modules\Auth\AuthController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller) {
        $controller->login($r);
    });
});


$router->add('POST', '/api/auth/register', function (Request $req) {
    $controller = new \CyberKavach\Modules\Auth\AuthController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller) {
        $controller->register($r);
    });
});

$router->add('POST', '/api/auth/otp/request', function (Request $req) {
    $controller = new \CyberKavach\Modules\Auth\AuthController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller) {
        $controller->requestOtp($r);
    });
});

$router->add('POST', '/api/auth/otp/verify', function (Request $req) {
    $controller = new \CyberKavach\Modules\Auth\AuthController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller) {
        $controller->verifyOtp($r);
    });
});

$router->add('POST', '/api/auth/password/reset', function (Request $req) {
    $controller = new \CyberKavach\Modules\Auth\AuthController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller) {
        $controller->resetPassword($r);
    });
});

$router->add('POST', '/api/auth/logout', function (Request $req) {
    $controller = new \CyberKavach\Modules\Auth\AuthController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller) {
        $controller->logout($r);
    });
});

$router->add('GET', '/api/auth/me', function (Request $req) {
    $controller = new \CyberKavach\Modules\Auth\AuthController();
    $controller->me($req);
});

// Approvals API
$router->add('POST', '/api/approvals', function (Request $req) {
    $controller = new \CyberKavach\Modules\Approvals\ApprovalsController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller) {
        $controller->create($r);
    });
});

$router->add('GET', '/api/approvals', function (Request $req) {
    $controller = new \CyberKavach\Modules\Approvals\ApprovalsController();
    $controller->list($req);
});

$router->add('GET', '/api/approvals/{ulid}', function (Request $req, $ulid) {
    $controller = new \CyberKavach\Modules\Approvals\ApprovalsController();
    $controller->get($req, $ulid);
});

$router->add('GET', '/api/approvals/{ulid}/timeline', function (Request $req, $ulid) {
    $controller = new \CyberKavach\Modules\Approvals\ApprovalsController();
    $controller->timeline($req, $ulid);
});

$router->add('POST', '/api/approvals/{ulid}/steps/{stepId}/action', function (Request $req, $ulid, $stepId) {
    $controller = new \CyberKavach\Modules\Approvals\ApprovalsController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller, $ulid, $stepId) {
        $controller->action($r, $ulid, $stepId);
    });
});

// Categories API
$router->add('GET', '/api/categories', function (Request $req) {
    $controller = new \CyberKavach\Modules\Events\CategoriesController();
    $controller->apiList($req);
});

$router->add('POST', '/api/categories', function (Request $req) {
    $controller = new \CyberKavach\Modules\Events\CategoriesController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller) {
        $controller->apiCreate($r);
    });
});

$router->add('POST', '/api/categories/{id}/update', function (Request $req, $id) {
    $controller = new \CyberKavach\Modules\Events\CategoriesController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller, $id) {
        $controller->apiUpdate($r, $id);
    });
});

$router->add('POST', '/api/categories/{id}/delete', function (Request $req, $id) {
    $controller = new \CyberKavach\Modules\Events\CategoriesController();
    return CSRFMiddleware::handle($req, function (Request $r) use ($controller, $id) {
        $controller->apiDelete($r, $id);
    });
});

$router->dispatch($request);
