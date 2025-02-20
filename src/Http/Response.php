<?php

namespace DinoEngine\Http;

class Response{

    private static $baseDir;

    public function __construct($baseDir){
        $this->baseDir = $baseDir;
    }

    public static function json(array $data, int $code = 200):void{
        header("Content-Type: application/json");
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    public static function redirect(string $url, int $code = 302):void{
        header("Location: $url", true, $code);
        exit;
    }

    public static function render(string $view, array $data = []):void{
        extract($data);
        
        ob_start();
        include_once self::$baseDir."/app/views/$view.php";
        $content = ob_get_clean();
        include_once self::$baseDir."/app/views/master.php";
    }

    public static function error(string $msg, int $code):void{
        http_response_code($code);
        echo $msg;
        exit;
    }
}