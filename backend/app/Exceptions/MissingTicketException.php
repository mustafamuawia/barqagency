<?php

namespace App\Exceptions;

class MissingTicketException extends DomainException
{
    public function __construct(string $message = 'لم يتم رفع تذكرة سفر لهذا الطلب بعد')
    {
        parent::__construct($message, 404);
    }
}
