<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterAgencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agency_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:3'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'agency_name.required' => 'اسم المكتب/الوكالة مطلوب.',
            'agency_name.string' => 'يجب أن يكون اسم المكتب نصاً صحيحاً.',
            'agency_name.max' => 'يجب ألا يتجاوز اسم المكتب 255 حرفاً.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.string' => 'يجب أن يكون البريد الإلكتروني نصاً صحيحاً.',
            'email.email' => 'يرجى إدخال بريد إلكتروني صحيح.',
            'email.max' => 'يجب ألا يتجاوز البريد الإلكتروني 255 حرفاً.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.string' => 'يجب أن تكون كلمة المرور نصاً صحيحاً.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 3 أحرف.',
            'phone.string' => 'يجب أن يكون رقم الهاتف نصاً صحيحاً.',
            'phone.max' => 'يجب ألا يتجاوز رقم الهاتف 50 حرفاً.',
            'address.string' => 'يجب أن يكون العنوان نصاً صحيحاً.',
            'address.max' => 'يجب ألا يتجاوز العنوان 500 حرفاً.',
        ];
    }
}
