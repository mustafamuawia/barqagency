<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\VerifyEmailRequest;
use App\Http\Resources\Api\V1\Auth\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function verify(VerifyEmailRequest $request)
    {
        $user = $this->authService->verifyEmail($request->validated('email'), $request->validated('token'));

        return response()->json([
            'message' => 'تم تفعيل بريدك الإلكتروني بنجاح، حسابك الآن بانتظار موافقة الإدارة',
            'user' => new UserResource($user),
        ]);
    }

    public function resend(Request $request)
    {
        $this->authService->resendVerificationEmail($request->user());

        return response()->json(['message' => 'تم إرسال رابط التفعيل مرة أخرى']);
    }
}
