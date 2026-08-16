<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $order = $this->route('order');

        return [
            'base_price' => 'required|numeric|min:0',
            'taxes' => 'nullable|numeric|min:0',
            'fees' => 'nullable|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:1000',

            // Departure details the admin confirms per flight of this order.
            'flights' => 'sometimes|array',
            'flights.*.id' => [
                'required',
                'integer',
                Rule::exists('order_segments', 'id')->where('order_id', $order?->id),
            ],
            'flights.*.departure_datetime' => 'nullable|date',
            'flights.*.departure_terminal' => 'nullable|string|max:50',

            'return_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'flights.*.id.exists' => 'إحدى الرحلات لا تنتمي إلى هذا الطلب.',
            'flights.*.departure_datetime.date' => 'وقت المغادرة غير صحيح.',
            'return_date.date' => 'تاريخ ووقت العودة غير صحيح.',
        ];
    }
}
