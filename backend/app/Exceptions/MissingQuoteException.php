<?php

namespace App\Exceptions;

class MissingQuoteException extends DomainException
{
    public function __construct(string $message = 'No quote available')
    {
        parent::__construct($message, 422);
    }
}
