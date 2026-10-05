<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Menu;
use App\Models\User;
use App\Support\CacheService;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    public static function effective(User $user): array
    {
        if ($user->role === Role::SuperAdmin) {
            $menus = Menu::all();
            $result = [];
            foreach ($menus as $menu) {
                $result[$menu->code] = [
                    'view' => true,
                    'create' => true,
                    'update' => true,
                    'delete' => true,
                ];
            }
            return $result;
        }

        return CacheService::remember(
            'permissions',
            "user.{$user->id}",
            function () use ($user) {
                return static::buildEffectivePermissions($user);
            },
            3600
        );
    }

    protected static function buildEffectivePermissions(User $user): array
    {
        $menus = Menu::all()->keyBy('code');
        $permissions = [];

        foreach ($menus as $menu) {
            $permissions[$menu->code] = [
                'view' => false,
                'create' => false,
                'update' => false,
                'delete' => false,
            ];
        }

        $rolePermission = \DB::table('role_menu_permissions')
            ->where('role', $user->role->value)
            ->get()
            ->keyBy('menu_id');

        foreach ($rolePermission as $menuId => $perm) {
            $menu = $menus->firstWhere('id', $menuId);
            if (!$menu) continue;

            $view = (bool) $perm->can_view;
            $permissions[$menu->code]['view'] = $view;
            $permissions[$menu->code]['create'] = $view ? (bool) $perm->can_create : false;
            $permissions[$menu->code]['update'] = $view ? (bool) $perm->can_update : false;
            $permissions[$menu->code]['delete'] = $view ? (bool) $perm->can_delete : false;
        }

        $userOverrides = \App\Models\UserMenuPermission::where('user_id', $user->id)->get();
        foreach ($userOverrides as $override) {
            $menu = $menus->firstWhere('id', $override->menu_id);
            if (!$menu) continue;

            if ($override->can_view !== null) {
                $permissions[$menu->code]['view'] = (bool) $override->can_view;
            }
            if ($override->can_create !== null) {
                $view = $permissions[$menu->code]['view'];
                $permissions[$menu->code]['create'] = $view ? (bool) $override->can_create : false;
            }
            if ($override->can_update !== null) {
                $view = $permissions[$menu->code]['view'];
                $permissions[$menu->code]['update'] = $view ? (bool) $override->can_update : false;
            }
            if ($override->can_delete !== null) {
                $view = $permissions[$menu->code]['view'];
                $permissions[$menu->code]['delete'] = $view ? (bool) $override->can_delete : false;
            }
        }

        return $permissions;
    }

    public static function can(User $user, string $menuCode, string $action): bool
    {
        $perms = self::effective($user);
        return $perms[$menuCode][$action] ?? false;
    }

    public static function invalidateForUser(User $user): void
    {
        CacheService::forget('permissions', "user.{$user->id}");
    }

    public static function invalidateForRole(Role $role): void
    {
        CacheService::flushGroup('permissions');
    }

    public static function visibleMenus(User $user): array
    {
        $perms = self::effective($user);
        $menuTree = [];

        $menus = Menu::orderBy('sort_order')->get();
        $menuMap = $menus->keyBy('code');

        foreach ($menus as $menu) {
            if (!isset($perms[$menu->code]['view']) || !$perms[$menu->code]['view']) {
                continue;
            }

            if ($menu->parent_id) {
                $parentCode = $menuMap->firstWhere('id', $menu->parent_id)->code ?? null;
                if ($parentCode && isset($menuTree[$parentCode])) {
                    $menuTree[$parentCode]['children'][] = self::formatMenu($menu, $perms);
                }
            } else {
                $menuTree[$menu->code] = self::formatMenu($menu, $perms);
            }
        }

        return array_values($menuTree);
    }

    protected static function formatMenu(Menu $menu, array $perms): array
    {
        $formatted = [
            'code' => $menu->code,
            'name' => $menu->name,
            'path' => $menu->path,
            'icon' => $menu->icon,
            'permissions' => $perms[$menu->code],
        ];

        $children = Menu::where('parent_id', $menu->id)->orderBy('sort_order')->get();
        foreach ($children as $child) {
            if (isset($perms[$child->code]['view']) && $perms[$child->code]['view']) {
                $formatted['children'][] = self::formatMenu($child, $perms);
            }
        }

        return $formatted;
    }
}