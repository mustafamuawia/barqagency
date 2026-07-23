<?php

namespace App\Exceptions;

class InvalidResetTokenException extends DomainException
{
    public function __construct(string $message = 'رابط إعادة تعيين كلمة المرور غير صالح أو منتهي الصلاحية')
    {
        parent::__construct($message, 422);
    }
}
