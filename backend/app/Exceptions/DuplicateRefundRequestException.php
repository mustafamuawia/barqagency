<?php

namespace App\Exceptions;

class DuplicateRefundRequestException extends DomainException
{
    public function __construct(string $message = 'يوجد بالفعل طلب استرداد نشط لهذا الحجز')
    {
        parent::__construct($message, 422);
    }
}
