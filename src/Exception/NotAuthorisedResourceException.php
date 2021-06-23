<?php


namespace EvansKim\Resourcery\Exception;


use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class NotAuthorisedResourceException extends HttpException
{
    public function __construct(int $statusCode = 403, string $message = null, Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        if(!$message){
            $message = "권한설정 에러";
        }
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
