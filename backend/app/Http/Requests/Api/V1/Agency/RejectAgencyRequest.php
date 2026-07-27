<?php

namespace App\Http\Requests\Api\V1\Agency;

use Illuminate\Foundation\Http\FormRequest;

class RejectAgencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:1000',
        ];
    }
}
