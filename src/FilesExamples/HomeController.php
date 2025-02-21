<?php

namespace App\Controllers;

use DinoEngine\Http\Response;

class HomeController
{
    public static function index(): void{
        Response::render('indexExample', ['title' => 'Inicio']);
    }
}