<?php

namespace App\Exceptions;

class MissingAgencyException extends DomainException
{
    public function __construct(string $message, int $statusCode = 422)
    {
        parent::__construct($message, $statusCode);
    }
}
