<?php

namespace App\Http\Controllers\Api\Access;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRolePermissionRequest;
use App\Http\Requests\UpdateUserPermissionRequest;
use App\Http\Resources\MenuResource;
use App\Models\User;
use App\Services\PermissionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $menus = \App\Models\Menu::orderBy('sort_order')->get();
        $roles = Role::cases();

        return ApiResponse::success([
            'menus' => MenuResource::collection($menus),
            'roles' => array_map(fn (Role $r) => ['value' => $r->value, 'label' => $r->label()], $roles),
        ]);
    }

    public function roleMatrix(Role $role): JsonResponse
    {
        $menus = \App\Models\Menu::orderBy('sort_order')->get();

        return ApiResponse::success([
            'role' => $role->value,
            'role_label' => $role->label(),
            'menus' => MenuResource::collection($menus),
            'matrix' => PermissionService::roleMatrix($role),
        ]);
    }

    public function updateRole(UpdateRolePermissionRequest $request, Role $role): JsonResponse
    {
        PermissionService::updateRolePermissions($role, $request->validated('permissions'));

        return ApiResponse::success(PermissionService::roleMatrix($role), 'Izin role berhasil diperbarui.');
    }

    public function userMatrix(User $user): JsonResponse
    {
        $menus = \App\Models\Menu::orderBy('sort_order')->get();

        return ApiResponse::success([
            'user' => new \App\Http\Resources\UserResource($user->load('department')),
            'role' => $user->role->value,
            'menus' => MenuResource::collection($menus),
            'overrides' => PermissionService::userOverrideMatrix($user),
            'effective' => PermissionService::effective($user),
        ]);
    }

    public function updateUserPermission(UpdateUserPermissionRequest $request, User $user): JsonResponse
    {
        PermissionService::updateUserPermissions($user, $request->validated('permissions'));

        return ApiResponse::success(PermissionService::userOverrideMatrix($user), 'Override izin pengguna berhasil diperbarui.');
    }
}
