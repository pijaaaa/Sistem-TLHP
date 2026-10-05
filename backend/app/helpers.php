<?php

use App\Enums\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Auth;

if (! function_exists('can_menu')) {
    function can_menu(?string $menuCode = null, string $action = 'view'): bool
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return false;
        }

        if ($user->role === Role::SuperAdmin) {
            return true;
        }

        return PermissionService::can($user, $menuCode, $action);
    }
}
