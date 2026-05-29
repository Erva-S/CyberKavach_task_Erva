<?php
namespace CyberKavach\Core;

class Router
{
    // Minimal skeleton: expand with PSR-7 Request/Response later
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        $handler = $this->routes[$method][$uri] ?? null;

        if (is_callable($handler)) {
            call_user_func($handler);
            return;
        }

        http_response_code(404);
        echo 'Not Found';
    }
}
