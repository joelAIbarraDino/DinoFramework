<?php

namespace DinoEngine\Core;

use DinoEngine\Helpers\Helpers;
use DinoEngine\Http\Request;
use DinoEngine\Http\Response;
use DinoFrame\Dino;

class Router{
    
    private array $routes = [];
    private string $nameApp;

    public function __construct(string $nameApp){
        $this->nameApp = $nameApp;
    }

    public function get(string $url, callable $fn, array $middlewares = []): void{
        $this->addRoute('get', $url, $fn, $middlewares);
    }

    public function post(string $url, callable $fn, array $middlewares = []): void{
        $this->addRoute('post', $url, $fn, $middlewares);
    }

    public function put(string $url, callable $fn, array $middlewares = []): void{
        $this->addRoute('put', $url, $fn, $middlewares);
    }

    public function delete(string $url, callable $fn, array $middlewares = []): void{
        $this->addRoute('delete', $url, $fn, $middlewares);
    }

    public function patch(string $url, callable $fn, array $middlewares = []): void{
        $this->addRoute('patch', $url, $fn, $middlewares);
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
                $params["nameApp"] =$this->nameApp;

                Request::setUrlParams($params);
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
            function (callable $stack, $middleware) {
                return function () use ($middleware, $stack) {
                    // Si es un string (nombre de la clase), instancia el middleware
                    if (is_string($middleware)) {
                        $middleware = new $middleware;
                    }

                    // Llama al método handle del middleware
                    $middleware->handle($stack);
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