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
            Role::SuperAdmin->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'master.employees' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'access.permissions' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'action_plans' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'follow_ups' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'follow_up_reviews' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'ia_monitoring' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'spi_review' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'external_status' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'inbox' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'reports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
            ],
            Role::AdminSpi->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.employees' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'access.permissions' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'action_plans' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'follow_ups' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'follow_up_reviews' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'ia_monitoring' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'spi_review' => ['view' => true, 'create' => true, 'update' => true, 'delete' => false],
                'external_status' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'inbox' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'reports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
            ],
            Role::InternalAudit->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.employees' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'access.permissions' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'action_plans' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'follow_ups' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'follow_up_reviews' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'ia_monitoring' => ['view' => true, 'create' => true, 'update' => true, 'delete' => false],
                'spi_review' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'external_status' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'inbox' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'reports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
            ],
            Role::ManagerDept->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.employees' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'access.permissions' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'action_plans' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'follow_ups' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'follow_up_reviews' => ['view' => true, 'create' => true, 'update' => true, 'delete' => false],
                'ia_monitoring' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'spi_review' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'external_status' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'inbox' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'reports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
            ],
            Role::StaffDept->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.employees' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'access.permissions' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'findings' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'action_plans' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'follow_ups' => ['view' => true, 'create' => true, 'update' => true, 'delete' => false],
                'follow_up_reviews' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'ia_monitoring' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'spi_review' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'external_status' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'inbox' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'reports' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
            ],
            Role::Kepala_spi->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.employees' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'access.permissions' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings' => ['view' => true, 'create' => false, 'update' => true, 'delete' => false],
                'action_plans' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'follow_ups' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'follow_up_reviews' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'ia_monitoring' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'spi_review' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'external_status' => ['view' => true, 'create' => true, 'update' => true, 'delete' => false],
                'inbox' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'reports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
            ],
            Role::ManagerIa->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.employees' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'access.permissions' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'action_plans' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'follow_ups' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'follow_up_reviews' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'ia_monitoring' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'spi_review' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'external_status' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'inbox' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'reports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
            ],
            Role::Direksi->value => [
                'dashboard' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.departments' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'master.employees' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'access.permissions' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'audit_trail' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'findings' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'action_plans' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'follow_ups' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'follow_up_reviews' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'ia_monitoring' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'spi_review' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'external_status' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'inbox' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
                'reports' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
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