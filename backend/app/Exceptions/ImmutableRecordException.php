<?php

namespace App\Exceptions;

class ImmutableRecordException extends DomainException
{
    public function __construct(string $message = 'هذا السجل غير قابل للتعديل أو الحذف')
    {
        parent::__construct($message, 409);
    }
}
