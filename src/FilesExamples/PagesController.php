<?php

namespace App\Controllers;

use DinoEngine\Http\Response;

class PagesController
{
    public static function index(): void{
        Response::render('pages/indexExample', ['title' => 'Inicio']);
    }
}