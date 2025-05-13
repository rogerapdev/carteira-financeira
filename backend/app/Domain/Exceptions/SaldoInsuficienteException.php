<?php

namespace App\Domain\Exceptions;

use Exception;

class SaldoInsuficienteException extends Exception
{
    public function __construct(string $message = "Saldo insuficiente para esta operação", int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 