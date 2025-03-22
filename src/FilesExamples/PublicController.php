<?php

namespace App\Controllers;

use DinoEngine\Http\Response;

class PublicController
{
    public static function index(string $nameApp): void{
        Response::render('public/index', [
            'nameApp'=>$nameApp, 
            'title' => 'Inicio'
        ]);
    }
}