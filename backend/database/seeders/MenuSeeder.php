<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            ['code' => 'dashboard', 'name' => 'Dashboard', 'path' => '/dashboard', 'icon' => 'layout-dashboard', 'parent_id' => null, 'sort_order' => 0, 'is_active' => true],
            ['code' => 'findings', 'name' => 'Temuan', 'path' => '/temuan', 'icon' => 'file-text', 'parent_id' => null, 'sort_order' => 1, 'is_active' => true],
            ['code' => 'action_plans', 'name' => 'Action Plan', 'path' => '/action-plan', 'icon' => 'clipboard-list', 'parent_id' => null, 'sort_order' => 2, 'is_active' => true],
            ['code' => 'follow_ups', 'name' => 'Tindak Lanjut', 'path' => '/tindak-lanjut', 'icon' => 'check-circle', 'parent_id' => null, 'sort_order' => 3, 'is_active' => true],
            ['code' => 'follow_up_reviews', 'name' => 'Persetujuan Manager', 'path' => '/persetujuan', 'icon' => 'thumbs-up', 'parent_id' => null, 'sort_order' => 4, 'is_active' => true],
            ['code' => 'ia_monitoring', 'name' => 'Pemantauan IA', 'path' => '/pemantauan', 'icon' => 'eye', 'parent_id' => null, 'sort_order' => 5, 'is_active' => true],
            ['code' => 'spi_review', 'name' => 'Review SPI', 'path' => '/review-spi', 'icon' => 'search', 'parent_id' => null, 'sort_order' => 6, 'is_active' => true],
            ['code' => 'external_status', 'name' => 'Status Eksternal', 'path' => '/status-eksternal', 'icon' => 'globe', 'parent_id' => null, 'sort_order' => 7, 'is_active' => false],
            ['code' => 'inbox', 'name' => 'Inbox Persetujuan', 'path' => '/inbox', 'icon' => 'inbox', 'parent_id' => null, 'sort_order' => 8, 'is_active' => false],
            ['code' => 'reports', 'name' => 'Monitoring & Laporan', 'path' => '/laporan', 'icon' => 'bar-chart', 'parent_id' => null, 'sort_order' => 9, 'is_active' => false],
            ['code' => 'audit_trail', 'name' => 'Log Aktivitas', 'path' => '/log-aktivitas', 'icon' => 'history', 'parent_id' => null, 'sort_order' => 10, 'is_active' => true],
            ['code' => 'exports', 'name' => 'Ekspor', 'path' => null, 'icon' => 'download', 'parent_id' => null, 'sort_order' => 13, 'is_active' => false],
            ['code' => 'master', 'name' => 'Master & Akses', 'path' => null, 'icon' => 'settings', 'parent_id' => null, 'sort_order' => 11, 'is_active' => true],
            ['code' => 'master.departments', 'name' => 'Departemen', 'path' => '/master/departemen', 'icon' => 'building', 'parent_id' => 'master', 'sort_order' => 0, 'is_active' => true],
            ['code' => 'master.employees', 'name' => 'Karyawan', 'path' => '/master/karyawan', 'icon' => 'users', 'parent_id' => 'master', 'sort_order' => 1, 'is_active' => true],
            ['code' => 'access.permissions', 'name' => 'Manajemen Akses', 'path' => '/akses', 'icon' => 'shield', 'parent_id' => 'master', 'sort_order' => 2, 'is_active' => true],
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
                    'is_active' => $menuData['is_active'],
                ]
            );

            $parentMap[$menuData['code']] = $menu->id;
        }
    }
}