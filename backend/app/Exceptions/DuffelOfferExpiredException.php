<?php

namespace App\Exceptions;

class DuffelOfferExpiredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('انتهت صلاحية هذا العرض. سيتم تحديث النتائج تلقائياً.', 409);
    }
}
