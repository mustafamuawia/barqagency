<?php

namespace App\Exceptions;

class EmailAlreadyVerifiedException extends DomainException
{
    public function __construct(string $message = 'تم التحقق من هذا البريد الإلكتروني مسبقاً')
    {
        parent::__construct($message, 422);
    }
}
