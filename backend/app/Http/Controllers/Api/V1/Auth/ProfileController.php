<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Resources\Api\V1\Auth\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function show(Request $request)
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $this->authService->updateProfile($request->user(), $request->validated());

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $this->authService->updatePassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('password'),
        );

        return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح']);
    }
}
