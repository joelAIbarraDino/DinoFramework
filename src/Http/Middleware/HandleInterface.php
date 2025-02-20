<?php

namespace DinoEngine\Middleware;

interface HandleInterface{
    public function handle(callable $next);
}