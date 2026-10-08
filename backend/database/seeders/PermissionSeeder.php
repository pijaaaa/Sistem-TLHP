<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Menu;
use App\Models\RoleMenuPermission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $menus = Menu::all()->keyBy('code');

        $permissions = [
             Role::AdminSpi->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'master.employees' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'access.permissions' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'exports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
             ],
             Role::ManagerIa->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.employees' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'access.permissions' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'exports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
            ],
            Role::ManagerDept->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.employees' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'access.permissions' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'exports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
            ],
            Role::StaffDept->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.employees' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'access.permissions' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'exports' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
            ],
        ];

        foreach ($permissions as $roleValue => $menuPerms) {
            foreach ($menuPerms as $menuCode => $perms) {
                $menu = $menus[$menuCode] ?? null;
                if (!$menu) continue;

                $view = $perms['view'];
                RoleMenuPermission::updateOrCreate(
                    ['role' => $roleValue, 'menu_id' => $menu->id],
                    [
                        'can_view' => $view,
                        'can_create' => $view ? $perms['create'] : false,
                        'can_update' => $view ? $perms['update'] : false,
                        'can_delete' => $view ? $perms['delete'] : false,
                    ]
                );
            }
        }
    }
}