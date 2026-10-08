<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeUserSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::all();
        $password = Hash::make(env('DEV_USER_PASSWORD', 'password'));
        
        $nikCounter = 1000;

        foreach ($departments as $dept) {
            $code = strtolower(str_replace([' & ', ' '], '_', $dept->code));
            
            // 1. Create Manager
            $managerRole = match($dept->code) {
                'IA' => Role::ManagerIa,
                'FINANCE_ICT' => Role::ManagerSpi, // For SPI, let's make the manager ManagerSpi. (Wait, FINANCE_ICT has AdminSpi, ManagerSpi, and ManagerDept. Let's handle special depts below)
                default => Role::ManagerDept,
            };

            if ($dept->code !== 'FINANCE_ICT' && $dept->code !== 'IA') {
                $this->createEmployeeAndUser(
                    nik: 'MGR' . $nikCounter++,
                    name: 'Manager ' . $dept->name,
                    position: 'Manager',
                    dept: $dept,
                    role: Role::ManagerDept,
                    username: 'mgr_' . $code,
                    password: $password
                );
            }

            // 2. Create Staff (2 PIC per dept)
            for ($i = 1; $i <= 2; $i++) {
                $this->createEmployeeAndUser(
                    nik: 'STF' . $nikCounter++,
                    name: 'Staff ' . $i . ' ' . $dept->name,
                    position: 'Staff',
                    dept: $dept,
                    role: Role::StaffDept,
                    username: 'pic_' . $i . '_' . $code,
                    password: $password
                );
            }
        }

        // Special Users (IA)
        $deptIa = Department::where('code', 'IA')->first();
        if ($deptIa) {
            $this->createEmployeeAndUser('MGR_IA', 'Manager Internal Audit', 'VP Internal Audit', $deptIa, Role::ManagerIa, 'manager_ia', $password);
        }

        // Special Users (FINANCE_ICT / SPI)
        $deptSpi = Department::where('code', 'FINANCE_ICT')->first();
        if ($deptSpi) {
            $this->createEmployeeAndUser('ADM_SPI', 'Admin SPI', 'Admin', $deptSpi, Role::AdminSpi, 'admin_spi', $password);
            $this->createEmployeeAndUser('MGR_SPI', 'Manager SPI', 'Manager SPI', $deptSpi, Role::ManagerSpi, 'manager_spi', $password);
            $this->createEmployeeAndUser('MGR_FIN', 'Manager Finance', 'Manager Finance', $deptSpi, Role::ManagerDept, 'manager_finance', $password);
            $this->createEmployeeAndUser('SUP_ADM', 'Super Admin', 'Super Admin', $deptSpi, Role::SuperAdmin, 'superadmin', $password);
            
            // The 2 staff for FINANCE_ICT were already created in loop, but let's ensure pic_finance exists for DummyDataSeeder
            $this->createEmployeeAndUser('STF_FIN', 'PIC Finance', 'Staff Finance', $deptSpi, Role::StaffDept, 'pic_finance', $password);
        }
    }

    private function createEmployeeAndUser($nik, $name, $position, $dept, $role, $username, $password)
    {
        $employee = Employee::firstOrCreate(
            ['nik' => $nik],
            [
                'name' => $name,
                'position' => $position,
                'department_id' => $dept->id,
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['username' => $username],
            [
                'name' => $name,
                'email' => $username . '@example.com',
                'password' => $password,
                'role' => $role->value,
                'department_id' => $dept->id,
                'employee_id' => $employee->id,
                'is_active' => true,
            ]
        );
    }
}