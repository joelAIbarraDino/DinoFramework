<?php

declare(strict_types=1);

namespace DinoEngine\Classes;

class Storage{

    public static function exists(string $path):bool{
        if(empty($path))
            return false;
        
        return file_exists($path);
    }

    public static function delete(string $path):bool{
        if(empty($path))
            return false;

        return unlink($path);
    }

    public static function save(string $file, string $path):bool{
        if(empty($path) || empty($file))
            return false;

        if(!is_dir($path))
            mkdir($path);

        if(!move_uploaded_file($file, $path))
            return false;

        return true;
    }

    public static function uniqName(string $extension):string{
        return md5( uniqid( (string)rand(), true ) ) .$extension;
    }

    public static function validateFormat(string $type, array $validFormats):bool{
        return in_array($type, $validFormats);
    }

}

