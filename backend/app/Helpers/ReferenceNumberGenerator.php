<?php

namespace App\Helpers;

class ReferenceNumberGenerator
{
    public static function make(string $prefix): string
    {
        return $prefix . '-' . now()->format('dmyH') . '-' . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
