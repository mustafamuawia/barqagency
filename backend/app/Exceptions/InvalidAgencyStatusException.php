<?php

namespace App\Exceptions;

class InvalidAgencyStatusException extends DomainException
{
    public function __construct(string $message = 'لا يمكن معالجة هذا الطلب في حالته الحالية')
    {
        parent::__construct($message, 422);
    }
}
