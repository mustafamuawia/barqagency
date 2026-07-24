<?php

namespace App\Exceptions;

class InactiveAccountException extends DomainException
{
    public function __construct(string $message = 'تم تعطيل هذا الحساب. يرجى التواصل مع مسؤول النظام')
    {
        parent::__construct($message, 403);
    }
}
