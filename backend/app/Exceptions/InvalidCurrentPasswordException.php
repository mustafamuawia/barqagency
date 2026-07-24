<?php

namespace App\Exceptions;

class InvalidCurrentPasswordException extends DomainException
{
    public function __construct()
    {
        parent::__construct('كلمة المرور الحالية غير صحيحة', 422);
    }
}
