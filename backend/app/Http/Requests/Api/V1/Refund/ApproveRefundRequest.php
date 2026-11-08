<?php

namespace App\Http\Requests\Api\V1\Refund;

use Illuminate\Foundation\Http\FormRequest;

class ApproveRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'penalty_amount'       => 'nullable|numeric|min:0',
            'admin_remarks'        => 'nullable|string|max:1000',
            'force'                => 'nullable|boolean',
            'manual_refund_amount' => 'nullable|numeric|min:0',
        ];
    }
}
