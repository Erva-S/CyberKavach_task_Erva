<?php
namespace CyberKavach\Core;

class Router
{
    private array $routes = [];
    private string $groupPrefix = '';
    private array $groupMiddleware = [];

    public function add(string $method, string $path, callable $handler, array $middleware = []): self
    {
        $method = strtoupper($method);
        $normalizedPath = $this->normalizePath($this->groupPrefix . $path);
        $this->routes[$method][$normalizedPath] = [
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];

        return $this;
    }

    public function get(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('DELETE', $path, $handler, $middleware);
    }

    public function options(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('OPTIONS', $path, $handler, $middleware);
    }

    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = $this->normalizePath($previousPrefix . '/' . trim($prefix, '/'));
        $this->groupMiddleware = array_merge($previousMiddleware, $middleware);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();

        // Exact match first
        if (isset($this->routes[$method][$path])) {
            $route = $this->routes[$method][$path];
            $this->runRouteMiddleware($request, $route['middleware'], function (Request $handledRequest) use ($route) {
                call_user_func($route['handler'], $handledRequest);
            });
            return;
        }

        // Simple param matching: /items/{id}
        if (!empty($this->routes[$method])) {
            foreach ($this->routes[$method] as $route => $definition) {
                $pattern = preg_replace('#\{[a-zA-Z0-9_]+\}#', '([^/]+)', $route);
                $pattern = "#^" . $pattern . "$#";
                if (preg_match($pattern, $path, $matches)) {
                    $route = $definition;
                    array_shift($matches);
                    $this->runRouteMiddleware($request, $route['middleware'], function (Request $handledRequest) use ($route, $matches) {
                        call_user_func($route['handler'], $handledRequest, ...$matches);
                    });
                    return;
                }
            }
        }

        http_response_code(404);
        echo '<h1>404 Not Found</h1>';
    }

    private function runRouteMiddleware(Request $request, array $middleware, callable $handler): void
    {
        $runner = array_reduce(
            array_reverse($middleware),
            function (callable $next, callable $current): callable {
                return function (Request $handledRequest) use ($current, $next): void {
                    $current($handledRequest, $next);
                };
            },
            function (Request $handledRequest) use ($handler): void {
                $handler($handledRequest);
            }
        );

        $runner($request);
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
