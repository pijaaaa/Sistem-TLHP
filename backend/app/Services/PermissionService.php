<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Menu;
use App\Models\RoleMenuPermission;
use App\Models\User;
use App\Models\UserMenuPermission;
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

    public static function roleMatrix(Role $role): array
    {
        $menus = Menu::orderBy('sort_order')->get();
        $perms = RoleMenuPermission::where('role', $role->value)->get()->keyBy('menu_id');

        $matrix = [];
        foreach ($menus as $menu) {
            $p = $perms[$menu->id] ?? null;
            $matrix[$menu->code] = $p
                ? [
                    'view' => (bool) $p->can_view,
                    'create' => (bool) $p->can_create,
                    'update' => (bool) $p->can_update,
                    'delete' => (bool) $p->can_delete,
                ]
                : ['view' => false, 'create' => false, 'update' => false, 'delete' => false];
        }

        return $matrix;
    }

    public static function updateRolePermissions(Role $role, array $matrix): void
    {
        $menus = Menu::all()->keyBy('code');

        foreach ($matrix as $menuCode => $perms) {
            $menu = $menus[$menuCode] ?? null;
            if (! $menu) {
                continue;
            }
            $view = (bool) $perms['view'];
            RoleMenuPermission::updateOrCreate(
                ['role' => $role->value, 'menu_id' => $menu->id],
                [
                    'can_view' => $view,
                    'can_create' => $view ? (bool) $perms['create'] : false,
                    'can_update' => $view ? (bool) $perms['update'] : false,
                    'can_delete' => $view ? (bool) $perms['delete'] : false,
                ]
            );
        }

        self::invalidateForRole($role);
    }

    public static function userOverrideMatrix(User $user): array
    {
        $menus = Menu::orderBy('sort_order')->get();
        $overrides = UserMenuPermission::where('user_id', $user->id)->get()->keyBy('menu_id');

        $matrix = [];
        foreach ($menus as $menu) {
            $o = $overrides[$menu->id] ?? null;
            $matrix[$menu->code] = $o
                ? [
                    'view' => $o->can_view === null ? null : (bool) $o->can_view,
                    'create' => $o->can_create === null ? null : (bool) $o->can_create,
                    'update' => $o->can_update === null ? null : (bool) $o->can_update,
                    'delete' => $o->can_delete === null ? null : (bool) $o->can_delete,
                ]
                : ['view' => null, 'create' => null, 'update' => null, 'delete' => null];
        }

        return $matrix;
    }

    public static function updateUserPermissions(User $user, array $matrix): void
    {
        $menus = Menu::all()->keyBy('code');

        foreach ($matrix as $menuCode => $perms) {
            $menu = $menus[$menuCode] ?? null;
            if (! $menu) {
                continue;
            }
            UserMenuPermission::updateOrCreate(
                ['user_id' => $user->id, 'menu_id' => $menu->id],
                [
                    'can_view' => $perms['view'] ?? null,
                    'can_create' => $perms['create'] ?? null,
                    'can_update' => $perms['update'] ?? null,
                    'can_delete' => $perms['delete'] ?? null,
                ]
            );
        }

        self::invalidateForUser($user);
    }

    public static function visibleMenus(User $user): array
    {
        $perms = self::effective($user);
        $menuTree = [];

        // Hanya ambil menu root (parent_id null)
        $rootMenus = Menu::whereNull('parent_id')->orderBy('sort_order')->get();

        foreach ($rootMenus as $menu) {
            if (isset($perms[$menu->code]['view']) && $perms[$menu->code]['view']) {
                $menuTree[] = self::formatMenu($menu, $perms);
            }
        }

        return $menuTree;
    }

    protected static function formatMenu(Menu $menu, array $perms): array
    {
        $formatted = [
            'code' => $menu->code,
            'name' => $menu->name,
            'path' => $menu->path,
            'icon' => $menu->icon,
            'permissions' => $perms[$menu->code],
            'children' => [],
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