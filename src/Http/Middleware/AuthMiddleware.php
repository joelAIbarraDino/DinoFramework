<?php

namespace DinoEngine\Middleware;

use DinoEngine\Http\Response;

class AuthMiddleware implements HandleInterface
{
    private string $redirectUrl;

    public function __construct(string $redirectUrl = '/')
    {
        $this->redirectUrl = $redirectUrl;
    }

    public function handle(callable $next): void
    {
        if (!$this->checkAuthentication()) {
            Response::redirect($this->redirectUrl);
            return;
        }

        $next();
    }

    private function checkAuthentication(): bool
    {
        // Verifica si la sesión está activa y si el usuario está autenticado
        if (session_status() === PHP_SESSION_NONE)
            session_start();

        return isset($_SESSION["id"]);
    }
}