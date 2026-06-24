<?php
namespace CyberKavach\Core;

use CyberKavach\Middleware\CSRFMiddleware;

class View
{
    /**
     * Renders a PHP view inside an optional layout.
     */
    public static function render(string $view, array $data = [], string $layout = 'public'): void
    {
        $viewFile = __DIR__ . '/../../views/' . ltrim($view, '/') . '.php';
        $layoutFile = __DIR__ . '/../../views/layouts/' . ltrim($layout, '/') . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        if (file_exists($layoutFile)) {
            include $layoutFile;
            return;
        }

        echo $content;
    }

    /**
     * Returns a meta tag with the CSRF token for embedding in <head>
     */
    public static function csrfMeta(): string
    {
        $token = CSRFMiddleware::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }

    /**
     * Returns a hidden input for forms
     */
    public static function csrfInput(): string
    {
        $token = CSRFMiddleware::getToken() ?? CSRFMiddleware::generateToken();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }

    /**
     * Small helper to render a script tag that exposes the token to JS (optional)
     */
    public static function csrfJsVar(): string
    {
        $token = CSRFMiddleware::getToken() ?? CSRFMiddleware::generateToken();
        $escaped = htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return "<script>window.CK_CSRF_TOKEN='" . $escaped . "';</script>";
    }
}
