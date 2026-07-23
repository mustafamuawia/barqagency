<?php

namespace App\Http\Requests\Api\V1\Report;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'agency_id' => 'nullable|exists:travel_agencies,id',
        ];
    }
}
