<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\PagesController;
use DinoEngine\Core\Database;
use DinoFrame\Dino;

$dbConfig = [
    "host"=>"127.0.0.1",
    "port"=>3306,
    "user"=>"root",
    "password"=>"",
    "database"=>"test"
];

$emailConfig = [
    "from"=>"miApp@dinozign.com",
    "to"=>"developer@dinozign.com",
    "name"=>"developer name",
    "host"=>"smtp.dinozign.com",
    "user"=>"miUser@dinozign.com",
    "password"=>"",
    "port"=>587
];

$dino = new Dino("Mi app dinozign", dirname(__DIR__), $dbConfig, Database::PDO_DRIVER, Dino::DEVELOPMENT_MODE, $emailConfig);

$dino->router->get('/', [PagesController::class, 'index']);

$dino->router->dispatch();