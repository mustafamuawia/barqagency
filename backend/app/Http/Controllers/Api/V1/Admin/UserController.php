<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreUserRequest;
use App\Http\Requests\Api\V1\Admin\UpdateUserRequest;
use App\Http\Resources\Api\V1\Admin\UserManagementResource;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $userService)
    {
    }

    public function index(Request $request)
    {
        return response()->json([
            'users' => UserManagementResource::collection($this->userService->list($request->query('trashed'))),
            'user_types' => $this->userService->assignableUserTypes(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->create($request->validated());

        return response()->json([
            'message' => 'تم إنشاء المستخدم بنجاح',
            'user' => new UserManagementResource($user),
        ], 201);
    }

    public function show(User $user)
    {
        return response()->json(new UserManagementResource($user->load('userType')));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user = $this->userService->update($user, $request->validated());

        return response()->json([
            'message' => 'تم تحديث المستخدم بنجاح',
            'user' => new UserManagementResource($user),
        ]);
    }

    public function toggleActive(Request $request, User $user)
    {
        $active = $request->boolean('is_active');
        $user = $this->userService->setActive($user, $active);

        return response()->json([
            'message' => $active ? 'تم تفعيل المستخدم' : 'تم تعطيل المستخدم',
            'user' => new UserManagementResource($user),
        ]);
    }

    public function destroy(User $user)
    {
        $this->userService->softDelete($user);

        return response()->json(['message' => 'تم حذف المستخدم بنجاح']);
    }

    public function restore(int $id)
    {
        $user = $this->userService->restore($id);

        return response()->json([
            'message' => 'تمت استعادة المستخدم بنجاح',
            'user' => new UserManagementResource($user),
        ]);
    }
}
