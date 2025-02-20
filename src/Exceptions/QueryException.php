<?php

namespace DinoEngine\Exceptions;

use Exception;

class QueryException extends Exception{

    public function __construct($mesage = null, $code = 0, Exception $previous = null)
    {
        parent::__construct($mesage, $code, $previous);
    }

}