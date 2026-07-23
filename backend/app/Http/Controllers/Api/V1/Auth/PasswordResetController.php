<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordResetService;

class PasswordResetController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwordResetService)
    {
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $this->passwordResetService->sendResetLink($request->validated('email'));

        return response()->json([
            'message' => 'إذا كان البريد الإلكتروني مسجلاً لدينا، فستصلك رسالة تحتوي على رابط إعادة تعيين كلمة المرور',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $this->passwordResetService->reset(
            $request->validated('token'),
            $request->validated('email'),
            $request->validated('password'),
        );

        return response()->json(['message' => 'تم إعادة تعيين كلمة المرور بنجاح']);
    }
}
