<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\MenuResource;
use App\Http\Resources\UserResource;
use App\Services\PermissionService;
use App\Support\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $request->validated();

        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return ApiResponse::error('Email atau password salah.', 401);
        }

        if (! $user->is_active) {
            return ApiResponse::error('Akun tidak aktif.', 403);
        }

        // Sengaja tanpa Auth::attempt(): API ini berbasis token, sehingga tidak
        // boleh membuat session web yang membuat logout tidak mencabut akses.
        $token = $user->createToken('auth-token')->plainTextToken;

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        // currentAccessToken() bisa mengembalikan TransientToken saat Sanctum
        // memakai cookie stateful, sehingga token tidak bisa dihapus dari sana.
        // Cari token lewat resolver resmi Sanctum agar pencabutan selalu berlaku.
        $plain = $request->bearerToken();

        if ($plain) {
            \Laravel\Sanctum\PersonalAccessToken::findToken($plain)?->delete();
        } else {
            $user->tokens()->delete();
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return ApiResponse::success(['message' => 'Logout berhasil.']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('department');
        $permissions = PermissionService::effective($user);
        $menus = PermissionService::visibleMenus($user);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'role' => $user->role->value,
            'department' => $user->department ? new \App\Http\Resources\DepartmentResource($user->department) : null,
            'permissions' => $permissions,
            'menus' => $menus,
        ]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return ApiResponse::error('Password saat ini tidak sesuai.', 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return ApiResponse::success(['message' => 'Password berhasil diubah.']);
    }
}