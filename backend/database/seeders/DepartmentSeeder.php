<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'FINANCE_ICT', 'name' => 'FINANCE & ICT'],
            ['code' => 'EKS', 'name' => 'EKS'],
            ['code' => 'EPT', 'name' => 'EPT'],
            ['code' => 'PRODUCTION', 'name' => 'PRODUCTION OPERATION'],
            ['code' => 'DWO', 'name' => 'DWO'],
            ['code' => 'QHSE', 'name' => 'QHSE'],
            ['code' => 'HCM', 'name' => 'HCM'],
            ['code' => 'SCM', 'name' => 'SCM'],
            ['code' => 'OPS_SUPPORT', 'name' => 'OPERATION SUPPORT'],
            ['code' => 'CORSEC', 'name' => 'CORSEC'],
            ['code' => 'EA', 'name' => 'EA'],
            ['code' => 'SPRM', 'name' => 'SPRM'],
            ['code' => 'IA', 'name' => 'IA'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                ['name' => $dept['name'], 'is_active' => true]
            );
        }
    }
}