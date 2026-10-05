<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\PermissionService;
use App\Services\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);

        return ApiResponse::success(
            UserResource::collection(UserService::paginated($perPage))
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = UserService::create($request->validated());

        return ApiResponse::success(new UserResource($user->load(['department', 'employee'])), 'Pengguna berhasil ditambahkan.', 201);
    }

    public function show(User $user): JsonResponse
    {
        return ApiResponse::success(new UserResource($user->load(['department', 'employee'])));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = UserService::update($user, $request->validated());

        return ApiResponse::success(new UserResource($user), 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): JsonResponse
    {
        UserService::delete($user);

        return ApiResponse::success(null, 'Pengguna berhasil dihapus.');
    }
}
