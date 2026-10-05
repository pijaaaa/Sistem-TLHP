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
                'findings.reports' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'findings.distribution' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings.list' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'action_plans' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'action_plan_reviews' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'evidence' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'assessments' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'verifications' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'exports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
            ],
            Role::ManagerIa->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.employees' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'access.permissions' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings.reports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings.distribution' => ['view' => true, 'create' => true, 'update' => true, 'delete' => false],
                'findings.list' => ['view' => true, 'create' => false, 'update' => true, 'delete' => false],
                'action_plans' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'action_plan_reviews' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'evidence' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'assessments' => ['view' => true, 'create' => true, 'update' => true, 'delete' => false],
                'verifications' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'exports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
            ],
            Role::ManagerDept->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.employees' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'access.permissions' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings.reports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings.distribution' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'findings.list' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'action_plans' => ['view' => true, 'create' => false, 'update' => true, 'delete' => false],
                'action_plan_reviews' => ['view' => true, 'create' => true, 'update' => true, 'delete' => false],
                'evidence' => ['view' => true, 'create' => true, 'update' => true, 'delete' => false],
                'assessments' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'verifications' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'exports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
            ],
            Role::StaffDept->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.employees' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'access.permissions' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings.reports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings.distribution' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'findings.list' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'action_plans' => ['view' => true, 'create' => true, 'update' => true, 'delete' => false],
                'action_plan_reviews' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'evidence' => ['view' => true, 'create' => true, 'update' => true, 'delete' => false],
                'assessments' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'verifications' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'exports' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
            ],
            Role::ManagerSpi->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.employees' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'access.permissions' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings.reports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings.distribution' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'findings.list' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'action_plans' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'action_plan_reviews' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'evidence' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'assessments' => ['view' => true, 'create' => true, 'update' => true, 'delete' => false],
                'verifications' => ['view' => true, 'create' => true, 'update' => true, 'delete' => false],
                'exports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
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