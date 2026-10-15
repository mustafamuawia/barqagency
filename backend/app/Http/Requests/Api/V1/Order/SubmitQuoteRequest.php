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
                'nullable',
                'integer',
                Rule::exists('order_segments', 'id')->where('order_id', $order?->id),
            ],
            'flights.*.airline_id' => 'nullable|integer|exists:airlines,id',
            'flights.*.airline_name' => 'required_without:flights.*.airline_id|nullable|string|max:255',
            'flights.*.departure_airport_name' => 'required_without:flights.*.id|nullable|string|max:255',
            'flights.*.arrival_airport_name' => 'required_without:flights.*.id|nullable|string|max:255',
            'flights.*.departure_datetime' => 'nullable|date',
            'flights.*.arrival_datetime' => 'nullable|date',
            'flights.*.departure_terminal' => 'nullable|string|max:50',

            'return_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'flights.*.id.exists' => 'إحدى الرحلات لا تنتمي إلى هذا الطلب.',
            'flights.*.departure_datetime.date' => 'وقت المغادرة غير صحيح.',
            'flights.*.arrival_datetime.date' => 'وقت الوصول غير صحيح.',
            'return_date.date' => 'تاريخ ووقت العودة غير صحيح.',
        ];
    }
}
