<?php

namespace App\Controllers;

use DinoEngine\Http\Response;

class HomeController
{
    public function index(): void{
        Response::render('indexExample', ['title' => 'Inicio']);
    }
}