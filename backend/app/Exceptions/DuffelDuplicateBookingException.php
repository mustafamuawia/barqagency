<?php

namespace App\Exceptions;

use DomainException;

class DuffelDuplicateBookingException extends DomainException
{
    public function __construct()
    {
        parent::__construct('تم إرسال طلب الحجز بالفعل، يرجى الانتظار.', 409);
    }
}
