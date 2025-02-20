<?php

namespace DinoEngine\Core;

use DinoEngine\Http\Request;
use DinoEngine\Http\Response;

class Router{
    
    private array $routes = [];

    public function get(string $url, callable $fn, array $middlewares = []): void{
        $this->addRoute('GET', $url, $fn, $middlewares);
    }

    public function post(string $url, callable $fn, array $middlewares = []): void{
        $this->addRoute('POST', $url, $fn, $middlewares);
    }

    public function put(string $url, callable $fn, array $middlewares = []): void{
        $this->addRoute('PUT', $url, $fn, $middlewares);
    }

    public function delete(string $url, callable $fn, array $middlewares = []): void{
        $this->addRoute('DELETE', $url, $fn, $middlewares);
    }

    public function patch(string $url, callable $fn, array $middlewares = []): void{
        $this->addRoute('PATCH', $url, $fn, $middlewares);
    }

    private function addRoute(string $method, string $url, callable $fn, array $middlewares): void{
        $this->routes[$method][$url] = [
            'handler' => $fn,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(): void{
        $method = Request::getMethod();
        $url = Request::getUrl();

        // Busca la ruta que coincida con la URL
        foreach ($this->routes[$method] as $route => $config) {
            $pattern = $this->convertRouteToPattern($route);

            if (preg_match($pattern, $url, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Ejecuta los middlewares antes de llamar al controlador
                $this->runMiddlewares($config['middlewares'], function () use ($config, $params) {
                    call_user_func_array($config['handler'], $params);
                });

                return;
            }
        }

        // Si no se encuentra la ruta, devuelve un error 404
        Response::error('Página no encontrada', 404);
    }

    private function runMiddlewares(array $middlewares, callable $next): void{
        $middlewareStack = $this->createMiddlewareStack($middlewares, $next);

        // Ejecuta el primer middleware
        $middlewareStack();
    }

    private function createMiddlewareStack(array $middlewares, callable $next): callable{
        return array_reduce(
            array_reverse($middlewares),
            function (callable $stack, string $middleware) {
                return function () use ($middleware, $stack) {
                    (new $middleware)->handle($stack);
                };
            },
            $next
        );
    }

    private function convertRouteToPattern(string $route): string{
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $route);
        return '#^' . $pattern . '$#';
    }

}