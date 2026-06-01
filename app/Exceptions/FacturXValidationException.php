<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class FacturXValidationException extends Exception
{
    protected $errors;

    public function __construct(string $message = '', array $errors = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
