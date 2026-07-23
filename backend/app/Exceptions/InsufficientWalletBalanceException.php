<?php

namespace App\Exceptions;

class InsufficientWalletBalanceException extends DomainException
{
    public function __construct(string $message = 'الرصيد غير كافٍ ويتجاوز حد الائتمان المسموح')
    {
        parent::__construct($message, 422);
    }
}
