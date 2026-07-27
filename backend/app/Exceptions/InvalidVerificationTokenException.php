<?php

namespace App\Exceptions;

class InvalidVerificationTokenException extends DomainException
{
    public function __construct(string $message = 'رابط التحقق غير صالح أو منتهي الصلاحية')
    {
        parent::__construct($message, 422);
    }
}
