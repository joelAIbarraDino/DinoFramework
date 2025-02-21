<?php

namespace DinoEngine\Http\Middleware;

interface HandleInterface{
    public function handle(callable $next);
}