<?php

namespace App\Exceptions;

class EmailNotVerifiedException extends DomainException
{
    public function __construct(string $message = 'لا يمكن اعتماد الوكالة قبل تحقق صاحب الحساب من بريده الإلكتروني')
    {
        parent::__construct($message, 422);
    }
}
