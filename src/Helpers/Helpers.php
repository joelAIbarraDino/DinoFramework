<?php

namespace DinoEngine\Helpers;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use Symfony\Component\Filesystem\Filesystem;
use TypeError;

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

    static function parseVal($input):mixed {

        if( strlen($input) === 0)
            return "";

        //verificar si es null
        $nullValues = ['null', 'NULL', ''];
        if(in_array(strtolower($input), $nullValues))
            return "";

        // Verificar booleano
        $boolValues = ['true', 'false' ,'on', 'off', 'yes', 'no'];
        if (in_array(strtolower($input), $boolValues)) {
            return filter_var($input, FILTER_VALIDATE_BOOLEAN);
        }
        
        // Verificar entero
        if (filter_var($input, FILTER_VALIDATE_INT) !== false) {
            return intval($input);
        }
        
        // Verificar float
        if (filter_var($input, FILTER_VALIDATE_FLOAT) !== false) {
            // Asegurarnos que no es un entero
            if (strpos($input, '.') !== false || stripos($input, 'e') !== false) {
                return floatval($input);
            }
        }
        
        // Si es numérico pero no entero ni float (como "123abc")
        if (is_numeric($input)) {
            return strval($input);
        }
        
        // Default: texto
        return strval($input);
    }

}

