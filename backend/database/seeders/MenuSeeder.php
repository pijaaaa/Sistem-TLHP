<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            ['code' => 'dashboard', 'name' => 'Dashboard', 'path' => '/dashboard', 'icon' => 'layout-dashboard', 'parent_id' => null, 'sort_order' => 0],
            ['code' => 'master.departments', 'name' => 'Departemen', 'path' => '/master/departments', 'icon' => 'building', 'parent_id' => 'master', 'sort_order' => 1],
            ['code' => 'master.employees', 'name' => 'Karyawan', 'path' => '/master/employees', 'icon' => 'users', 'parent_id' => 'master', 'sort_order' => 2],
            ['code' => 'access.permissions', 'name' => 'Manajemen Akses', 'path' => '/access/permissions', 'icon' => 'shield', 'parent_id' => null, 'sort_order' => 3],
            ['code' => 'audit_trail', 'name' => 'Audit Trail', 'path' => '/audit-trail', 'icon' => 'clipboard-list', 'parent_id' => null, 'sort_order' => 4],
            ['code' => 'findings.reports', 'name' => 'Laporan Temuan', 'path' => '/findings/reports', 'icon' => 'folder', 'parent_id' => 'findings', 'sort_order' => 5],
            ['code' => 'findings.distribution', 'name' => 'Distribusi Temuan', 'path' => '/findings/distribution', 'icon' => 'send', 'parent_id' => 'findings', 'sort_order' => 6],
            ['code' => 'findings.list', 'name' => 'Daftar Temuan', 'path' => '/findings', 'icon' => 'file-text', 'parent_id' => 'findings', 'sort_order' => 7],
            ['code' => 'action_plans', 'name' => 'Rencana Aksi', 'path' => '/action-plans', 'icon' => 'clipboard-edit', 'parent_id' => null, 'sort_order' => 8],
            ['code' => 'action_plan_reviews', 'name' => 'Review Rencana Aksi', 'path' => '/action-plan-reviews', 'icon' => 'clipboard-check', 'parent_id' => null, 'sort_order' => 9],
            ['code' => 'evidence', 'name' => 'Evidence', 'path' => '/evidence', 'icon' => 'upload', 'parent_id' => null, 'sort_order' => 10],
            ['code' => 'assessments', 'name' => 'Assessment', 'path' => '/assessments', 'icon' => 'scale', 'parent_id' => null, 'sort_order' => 11],
            ['code' => 'verifications', 'name' => 'Verifikasi', 'path' => '/verifications', 'icon' => 'search', 'parent_id' => null, 'sort_order' => 12],
            ['code' => 'exports', 'name' => 'Ekspor', 'path' => '/exports', 'icon' => 'download', 'parent_id' => null, 'sort_order' => 13],
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