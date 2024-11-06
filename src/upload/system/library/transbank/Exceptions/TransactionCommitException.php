<?php
namespace Transbank\Exceptions;
class TransactionCommitException extends \Exception
{
    public function __construct($message = "El token webpay es requerido", $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
