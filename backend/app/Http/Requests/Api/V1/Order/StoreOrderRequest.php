<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agency_id' => 'sometimes|exists:travel_agencies,id',
            'trip_type' => 'required|in:one_way,round_trip,multi_city',
            'departure_date' => 'required|date|after_or_equal:today',
            'weight' => 'nullable|integer|min:1|max:500',
            'return_date' => 'required_if:trip_type,round_trip|nullable|date|after:departure_date',
            // Every segment field needs a rule: as soon as one `segments.*` rule exists,
            // validated() returns only the nested keys that were validated.
            'segments' => 'required|array|min:1',
            'segments.*.airline_id' => 'nullable|integer|exists:airlines,id',
            'segments.*.departure_airport_id' => 'nullable|integer|exists:airports,id',
            'segments.*.departure_airport_name' => 'required_without:segments.*.departure_airport_id|nullable|string|max:255',
            'segments.*.arrival_airport_id' => 'nullable|integer|exists:airports,id',
            'segments.*.arrival_airport_name' => 'required_without:segments.*.arrival_airport_id|nullable|string|max:255',
            'segments.*.cabin_class_id' => 'required|integer|exists:cabin_classes,id',
            'segments.*.departure_datetime' => 'nullable|date',
            'segments.*.arrival_datetime' => 'nullable|date',
            'segments.*.flight_number' => 'nullable|string|max:20',
            'segments.*.sequence' => 'nullable|integer|min:1',
            // Only the head count per passenger type is submitted with the request — the admin
            // prices against it, and the agency fills in the traveller details on approval.
            'passenger_counts' => 'required|array|min:1',
            'passenger_counts.*.passenger_type_id' => 'required|integer|distinct|exists:passenger_types,id',
            'passenger_counts.*.count' => 'required|integer|min:0|max:9',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $total = collect($this->input('passenger_counts', []))->sum('count');

            if ($total < 1) {
                $validator->errors()->add('passenger_counts', 'يجب إضافة راكب واحد على الأقل.');
            } elseif ($total > 9) {
                $validator->errors()->add('passenger_counts', 'لا يمكن أن يتجاوز إجمالي عدد الركاب 9 ركاب.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'trip_type.required' => 'نوع الرحلة مطلوب.',
            'trip_type.in' => 'نوع الرحلة غير صحيح.',
            'departure_date.required' => 'تاريخ المغادرة مطلوب.',
            'departure_date.after_or_equal' => 'يجب ألا يكون تاريخ المغادرة في الماضي.',
            'return_date.required_if' => 'تاريخ العودة مطلوب لرحلات الذهاب والعودة.',
            'return_date.after' => 'يجب أن يكون تاريخ العودة بعد تاريخ المغادرة.',
            'weight.integer' => 'يجب أن يكون الوزن رقماً صحيحاً.',
            'weight.min' => 'يجب أن يكون الوزن 1 كجم على الأقل.',
            'weight.max' => 'لا يمكن أن يتجاوز الوزن 500 كجم.',
            'segments.required' => 'تفاصيل خط السير مطلوبة.',
            'segments.*.cabin_class_id.required' => 'درجة السفر مطلوبة.',
            'segments.*.departure_airport_name.required_without' => 'مطار المغادرة مطلوب.',
            'segments.*.arrival_airport_name.required_without' => 'مطار الوصول مطلوب.',
            'passenger_counts.required' => 'عدد الركاب مطلوب.',
            'passenger_counts.*.passenger_type_id.required' => 'نوع الراكب مطلوب.',
            'passenger_counts.*.passenger_type_id.exists' => 'نوع الراكب غير صحيح.',
            'passenger_counts.*.count.required' => 'عدد الركاب مطلوب.',
            'passenger_counts.*.count.integer' => 'يجب أن يكون عدد الركاب رقماً صحيحاً.',
            'passenger_counts.*.count.min' => 'لا يمكن أن يكون عدد الركاب بالسالب.',
            'passenger_counts.*.count.max' => 'لا يمكن أن يتجاوز عدد الركاب من النوع الواحد 9 ركاب.',
        ];
    }
}
