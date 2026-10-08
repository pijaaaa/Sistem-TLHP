<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin SPI',
                'email' => 'admin_spi@example.com',
                'username' => 'admin_spi',
                'role' => Role::AdminSpi,
                'department_code' => 'SPI',
            ],
            [
                'name' => 'Kepala SPI',
                'email' => 'kepala_spi@example.com',
                'username' => 'kepala_spi',
                'role' => Role::Kepala_spi,
                'department_code' => 'SPI',
            ],
            [
                'name' => 'Internal Audit',
                'email' => 'internal_audit@example.com',
                'username' => 'internal_audit',
                'role' => Role::InternalAudit,
                'department_code' => 'IA',
            ],
            [
                'name' => 'Manager IA',
                'email' => 'manager_ia@example.com',
                'username' => 'manager_ia',
                'role' => Role::ManagerIa,
                'department_code' => 'IA',
            ],
            [
                'name' => 'Manager IA (IA Auditee)',
                'email' => 'manager_ia_auditee@example.com',
                'username' => 'manager_ia_auditee',
                'role' => Role::ManagerDept,
                'department_code' => 'IA',
            ],
            [
                'name' => 'PIC IA',
                'email' => 'pic_ia@example.com',
                'username' => 'pic_ia',
                'role' => Role::StaffDept,
                'department_code' => 'IA',
            ],
            [
                'name' => 'Manager FINANCE',
                'email' => 'manager_finance@example.com',
                'username' => 'manager_finance',
                'role' => Role::ManagerDept,
                'department_code' => 'FINANCE_ICT',
            ],
            [
                'name' => 'PIC FINANCE',
                'email' => 'pic_finance@example.com',
                'username' => 'pic_finance',
                'role' => Role::StaffDept,
                'department_code' => 'FINANCE_ICT',
            ],
            [
                'name' => 'Direksi',
                'email' => 'direksi@example.com',
                'username' => 'direksi',
                'role' => Role::Direksi,
                'department_code' => 'DIREKSI',
            ],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'username' => 'superadmin',
                'role' => Role::SuperAdmin,
                'department_code' => 'SPI',
            ],
        ];

        foreach ($users as $userData) {
            $department = Department::where('code', $userData['department_code'])->first();
            if (!$department) continue;

            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'password' => Hash::make(env('DEV_USER_PASSWORD', 'password')),
                    'role' => $userData['role']->value,
                    'department_id' => $department->id,
                    'is_active' => true,
                ]
            );
        }
    }
}