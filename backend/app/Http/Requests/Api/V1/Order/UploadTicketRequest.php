<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

class UploadTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'ticket.required' => 'يرجى إرفاق ملف التذكرة.',
            'ticket.mimes' => 'يجب أن يكون ملف التذكرة بصيغة PDF.',
            'ticket.max' => 'الحد الأقصى لحجم ملف التذكرة 10 ميجابايت.',
        ];
    }
}
