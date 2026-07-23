<?php

namespace App\Exceptions;

class IneligibleRefundOrderException extends DomainException
{
    public function __construct(string $message = 'هذا الطلب غير مؤهل لطلب استرداد')
    {
        parent::__construct($message, 422);
    }
}
