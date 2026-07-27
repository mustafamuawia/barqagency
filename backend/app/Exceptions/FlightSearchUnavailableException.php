<?php

namespace App\Exceptions;

class FlightSearchUnavailableException extends DomainException
{
    public function __construct(string $message = 'تعذر الاتصال بخدمة البحث عن الرحلات، يرجى المحاولة لاحقًا')
    {
        parent::__construct($message, 503);
    }
}
