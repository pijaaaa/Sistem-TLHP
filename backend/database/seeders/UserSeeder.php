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
        $deptSpi = Department::where('code', 'FINANCE_ICT')->first();
        $deptIa = Department::where('code', 'IA')->first();

        $users = [
            [
                'name' => 'Admin SPI',
                'email' => 'admin_spi@example.com',
                'username' => 'admin_spi',
                'role' => Role::AdminSpi,
                'department_code' => 'FINANCE_ICT',
                'is_manager' => true,
            ],
            [
                'name' => 'Manager IA',
                'email' => 'manager_ia@example.com',
                'username' => 'manager_ia',
                'role' => Role::ManagerIa,
                'department_code' => 'IA',
                'is_manager' => true,
            ],
            [
                'name' => 'Manager FINANCE',
                'email' => 'manager_finance@example.com',
                'username' => 'manager_finance',
                'role' => Role::ManagerDept,
                'department_code' => 'FINANCE_ICT',
                'is_manager' => true,
            ],
            [
                'name' => 'PIC FINANCE',
                'email' => 'pic_finance@example.com',
                'username' => 'pic_finance',
                'role' => Role::StaffDept,
                'department_code' => 'FINANCE_ICT',
                'is_manager' => false,
            ],
            [
                'name' => 'Manager SPI',
                'email' => 'manager_spi@example.com',
                'username' => 'manager_spi',
                'role' => Role::ManagerSpi,
                'department_code' => 'FINANCE_ICT',
                'is_manager' => true,
            ],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'username' => 'superadmin',
                'role' => Role::SuperAdmin,
                'department_code' => 'FINANCE_ICT',
                'is_manager' => true,
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