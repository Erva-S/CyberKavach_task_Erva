<?php
namespace CyberKavach\Middleware;

use CyberKavach\Core\Request;
use CyberKavach\Core\Response;
use CyberKavach\Core\Auth;
use CyberKavach\Core\RBAC;

class RBACMiddleware
{
    public static function handle(Request $request, callable $next, string $module, string $action)
    {
        $auth = new Auth();
        if (!$auth->check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }

        $user = $auth->user();
        if (!$user) {
            Response::json(['error' => 'Unauthorized'], 401);
        }

        $rbac = new RBAC();
        $has = $rbac->hasPermission((int)$user['id'], $module, $action);
        if (!$has) {
            Response::json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
