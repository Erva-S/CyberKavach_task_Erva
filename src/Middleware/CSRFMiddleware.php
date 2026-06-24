<?php
namespace CyberKavach\Middleware;

use CyberKavach\Core\Request;
use CyberKavach\Core\Response;

class CSRFMiddleware
{
    public static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function generateToken(): string
    {
        self::ensureSession();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function getToken(): ?string
    {
        self::ensureSession();
        return $_SESSION['_csrf_token'] ?? null;
    }

    public static function validate(Request $request): bool
    {
        self::ensureSession();
        $method = $request->method();
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }

        $token = null;
        // header takes precedence for AJAX
        $token = $request->header('X-CSRF-Token') ?? $token;

        // body field
        $body = $request->json() ?? $request->all();
        if (empty($token) && is_array($body) && isset($body['_csrf'])) {
            $token = $body['_csrf'];
        }

        if (empty($token) && isset($_POST['_csrf'])) {
            $token = $_POST['_csrf'];
        }

        $stored = $_SESSION['_csrf_token'] ?? null;
        if (!$stored || !$token) {
            return false;
        }

        $ok = hash_equals($stored, $token);
        if ($ok) {
            // rotate token after successful use
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $ok;
    }

    public static function handle(Request $request, callable $next)
    {
        if (!self::validate($request)) {
            return Response::json(['error' => 'Invalid or missing CSRF token'], 403);
        }

        return $next($request);
    }
}
