<?php

namespace App\Exceptions;

use DomainException;

class DuffelBookingUncertainException extends DomainException
{
    private string $attemptUuid;

    public function __construct(string $attemptUuid = '')
    {
        parent::__construct(
            'لم يتمكن النظام من التحقق من حالة الحجز بسبب انقطاع الاتصال. يرجى التحقق من طلباتك قبل المحاولة مجدداً.',
            202
        );
        $this->attemptUuid = $attemptUuid;
    }

    public function getAttemptUuid(): string
    {
        return $this->attemptUuid;
    }
}
