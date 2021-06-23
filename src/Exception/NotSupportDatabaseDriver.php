<?php


namespace EvansKim\Resourcery\Exception;


use Exception;
use Throwable;

class NotSupportDatabaseDriver extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if(!$message){
            $message = 'Mysql, Sqlite 만 가능합니다.';
        }
        parent::__construct($message, $code, $previous);
    }
}
