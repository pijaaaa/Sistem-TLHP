<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'SPI', 'name' => 'SPI', 'is_auditee' => false],
            ['code' => 'IA', 'name' => 'IA', 'is_auditee' => true],
            ['code' => 'DIREKSI', 'name' => 'DIREKSI', 'is_auditee' => false],
            ['code' => 'FINANCE_ICT', 'name' => 'FINANCE & ICT', 'is_auditee' => true],
            ['code' => 'EKS', 'name' => 'EKS', 'is_auditee' => true],
            ['code' => 'EPT', 'name' => 'EPT', 'is_auditee' => true],
            ['code' => 'PRODUCTION', 'name' => 'PRODUCTION OPERATION', 'is_auditee' => true],
            ['code' => 'DWO', 'name' => 'DWO', 'is_auditee' => true],
            ['code' => 'QHSE', 'name' => 'QHSE', 'is_auditee' => true],
            ['code' => 'HCM', 'name' => 'HCM', 'is_auditee' => true],
            ['code' => 'SCM', 'name' => 'SCM', 'is_auditee' => true],
            ['code' => 'OPS_SUPPORT', 'name' => 'OPERATION SUPPORT', 'is_auditee' => true],
            ['code' => 'CORSEC', 'name' => 'CORSEC', 'is_auditee' => true],
            ['code' => 'EA', 'name' => 'EA', 'is_auditee' => true],
            ['code' => 'SPRM', 'name' => 'SPRM', 'is_auditee' => true],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                ['name' => $dept['name'], 'is_active' => true, 'is_auditee' => $dept['is_auditee']]
            );
        }
    }
}