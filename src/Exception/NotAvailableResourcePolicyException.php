<?php


namespace EvansKim\Resourcery\Exception;


use Exception;
use Throwable;

class NotAvailableResourcePolicyException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if(!$message){
            $message = "요청한 권한검사 클래스가 없습니다.";
        }
        parent::__construct($message, $code, $previous);
    }
}
