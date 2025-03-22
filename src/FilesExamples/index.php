<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\PublicController;
use DinoEngine\Core\Database;
use DinoFrame\Dino;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$dbConfig = [
    "host"=>$_ENV['DB_HOST'],
    "port"=>$_ENV['DB_PORT'],
    "user"=>$_ENV['DB_USER'],
    "password"=>$_ENV['DB_PASS'],
    "database"=>$_ENV['DB_DATABASE'],
    "driver"=>Database::PDO_DRIVER
];

$emailConfig = [
    "from"=>$_ENV['MAIL_DEBUG_FROM'],
    "to"=>$_ENV['MAIL_DEBUG_TO'],
    "name"=>$_ENV['MAIL_DEBUG_NAME'],
    "host"=>$_ENV['MAIL_DEBUG_HOST'],
    "user"=>$_ENV['MAIL_DEBUG_USER'],
    "password"=>$_ENV['MAIL_DEBUG_PASS'],
    "port"=>$_ENV['MAIL_DEBUG_PORT']
];

$dino = new Dino("Mi app dinozign", dirname(__DIR__), Dino::DEVELOPMENT_MODE, $dbConfig, $emailConfig);

$dino->router->get('/', [PublicController::class, 'index']);

$dino->router->dispatch();