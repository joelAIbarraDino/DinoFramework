<?php

namespace DinoEngine\Helpers;

use Symfony\Component\Filesystem\Filesystem;

class Helpers{

    static function pathExists(string $path):void{
        $filesystem = new Filesystem();
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

