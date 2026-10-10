<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The agency supplies the traveller details at the moment it approves the quote — the
 * order itself was requested with nothing but a head count per passenger type. Orders
 * created before that change already carry full details, so the payload is optional and
 * the service re-checks that no passenger is left incomplete.
 */
class ApproveOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'passengers' => 'sometimes|array',
            'passengers.*.id' => 'required|integer',
            'passengers.*.title' => 'nullable|string|max:20',
            'passengers.*.first_name' => 'required|string|max:100',
            'passengers.*.last_name' => 'required|string|max:100',
            'passengers.*.date_of_birth' => 'required|date|before:today',
            'passengers.*.nationality' => 'required|string|max:100',
            'passengers.*.passport_number' => 'required|string|max:50',
            'passengers.*.passport_expiry' => 'required|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'passengers.*.id.required' => 'تعذر تحديد الراكب المطلوب تعديله.',
            'passengers.*.first_name.required' => 'الاسم الأول للراكب مطلوب.',
            'passengers.*.last_name.required' => 'اسم عائلة الراكب مطلوب.',
            'passengers.*.date_of_birth.required' => 'تاريخ ميلاد الراكب مطلوب.',
            'passengers.*.date_of_birth.before' => 'يجب أن يكون تاريخ الميلاد في الماضي.',
            'passengers.*.nationality.required' => 'جنسية الراكب مطلوبة.',
            'passengers.*.passport_number.required' => 'رقم جواز سفر الراكب مطلوب.',
            'passengers.*.passport_expiry.required' => 'تاريخ انتهاء جواز السفر مطلوب.',
            'passengers.*.passport_expiry.after' => 'يجب أن يكون جواز السفر ساري المفعول.',
        ];
    }
}
