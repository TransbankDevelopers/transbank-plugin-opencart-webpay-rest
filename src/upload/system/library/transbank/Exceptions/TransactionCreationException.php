<?php
namespace Transbank\Exceptions;
class TransactionCreationException extends \Exception
{
    public function __construct($message = "Error al crear la transacción", $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
