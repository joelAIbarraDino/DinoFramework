<?php

namespace DinoEngine\Helpers;

function pathExists(string $path):void{
    if(!is_dir($path))
        mkdir($path);
}

function debuguear($variable) : string {
    echo "<pre>";
    var_dump($variable);
    echo "</pre>";
    exit;
}
