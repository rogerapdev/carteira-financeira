<?php

namespace App\Domain\Exceptions;

use Exception;

class TransacaoException extends Exception
{
    public function __construct(string $message = "Erro na transação", int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 