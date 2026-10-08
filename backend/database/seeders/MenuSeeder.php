<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            ['code' => 'dashboard', 'name' => 'Dashboard', 'path' => '/', 'icon' => 'layout-dashboard', 'parent_id' => null, 'sort_order' => 0],
            ['code' => 'master.departments', 'name' => 'Departemen', 'path' => '/master/departments', 'icon' => 'building', 'parent_id' => 'master', 'sort_order' => 1],
            ['code' => 'master.employees', 'name' => 'Karyawan', 'path' => '/master/employees', 'icon' => 'users', 'parent_id' => 'master', 'sort_order' => 2],
            ['code' => 'access.permissions', 'name' => 'Manajemen Akses', 'path' => '/access/permissions', 'icon' => 'shield', 'parent_id' => null, 'sort_order' => 3],
            ['code' => 'audit_trail', 'name' => 'Log Aktivitas', 'path' => '/audit-trail', 'icon' => 'clipboard-list', 'parent_id' => null, 'sort_order' => 4],
            ['code' => 'findings', 'name' => 'Temuan', 'path' => '/temuan', 'icon' => 'file-text', 'parent_id' => null, 'sort_order' => 5],
            ['code' => 'exports', 'name' => 'Ekspor', 'path' => '/exports', 'icon' => 'download', 'parent_id' => null, 'sort_order' => 6],
        ];

        $parentMap = [];
        foreach ($menus as $menuData) {
            $parentId = null;
            if (isset($menuData['parent_id']) && is_string($menuData['parent_id'])) {
                if (isset($parentMap[$menuData['parent_id']])) {
                    $parentId = $parentMap[$menuData['parent_id']];
                }
            }

            $menu = Menu::firstOrCreate(
                ['code' => $menuData['code']],
                [
                    'name' => $menuData['name'],
                    'path' => $menuData['path'],
                    'icon' => $menuData['icon'],
                    'parent_id' => $parentId,
                    'sort_order' => $menuData['sort_order'],
                    'is_active' => true,
                ]
            );

            $parentMap[$menuData['code']] = $menu->id;
        }
    }
}