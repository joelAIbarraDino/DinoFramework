<?php

namespace DinoEngine\Helpers;

class Helpers{

    static function pathExists(string $path):void{
        if(!is_dir($path))
            mkdir($path);
    }

    static function debuguear($variable) : string {
        echo "<pre>";
        var_dump($variable);
        echo "</pre>";
        exit;
    }

}

