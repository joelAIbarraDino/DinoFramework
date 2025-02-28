<?php

namespace DinoEngine\Http;

use DinoEngine\Helpers\Helpers;

class Request{
    
    private static array $urlParams = [];

    public static function getMethod():string{
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public static function isPOST():bool{
        return self::getMethod() === 'post';
    }

    public static function isGET():bool{
        return self::getMethod() === 'get';
    }

    public static function isPUT():bool{
        return self::getMethod() === 'put';
    }

    public static function isDELETE():bool{
        return self::getMethod() === 'delete';
    }

    public static function getURL():string{
        return $_SERVER['PATH_INFO']??'/';
    }

    public static function getPostData(): array|bool{
        if (empty($_POST)) {
            return false;
        }

        $data = [];
        foreach ($_POST as $key => $value) {
            if(is_array($value)){
                $arrayElements = [];

                foreach($value as $arrayValueElement)
                    $arrayElements[] = filter_var($arrayValueElement, FILTER_SANITIZE_SPECIAL_CHARS);

                $data[$key] = $arrayElements;
            }else
                $data[$key] = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        return $data;
    }

    public static function getQueryParams(): array|bool{
        if (empty($_GET)) {
            return false;
        }

        $data = [];
        foreach ($_POST as $key => $value) {
            if(is_array($value)){
                $arrayElements = [];

                foreach($value as $arrayValueElement)
                    $arrayElements[] = filter_var($arrayValueElement, FILTER_SANITIZE_SPECIAL_CHARS);

                $data[$key] = $arrayElements;
            }else
                $data[$key] = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        return $data;
    }

    public static function getBody(): array{
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    public static function getUrlParams():array{
        return self::$urlParams;
    }

    public static function setUrlParams(array $params):void{
        self::$urlParams = $params;
    }
}

