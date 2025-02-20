<?php

namespace DinoEngine\Http;

class Request{
    
    /**
     * @return string string lower request method
     */
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
            $data[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        return $data;
    }

    public static function getQueryParams(): array|bool{
        if (empty($_GET)) {
            return false;
        }

        $data = [];
        foreach ($_GET as $key => $value) {
            $data[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        return $data;
    }

    public static function getBody(): array{
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
}

