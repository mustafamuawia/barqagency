<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterAgencyRequest;
use App\Http\Resources\Api\V1\Auth\UserResource;
use App\Services\Auth\AuthService;

class RegisterController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function registerAgency(RegisterAgencyRequest $request)
    {
        $result = $this->authService->registerAgency($request->validated());

        return response()->json([
            'message' => 'Agency account registered successfully',
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
        ], 201);
    }
}
