<?php

namespace App\Http\Requests\Api\V1\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePointSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'money_amount'             => 'required|numeric|min:0.01',
            'point_amount'             => 'required|numeric|min:0.01',
            'agency_commission_amount' => 'sometimes|numeric|min:0',
            'usd_to_sar_rate'          => 'sometimes|numeric|min:0.01',
        ];
    }
}
