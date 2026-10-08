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
        $departments = Department::where('is_auditee', true)->get();
        $password = Hash::make(env('DEV_USER_PASSWORD', 'password'));
        
        $nikCounter = 1000;

        foreach ($departments as $dept) {
            $code = strtolower(str_replace([' & ', ' '], '_', $dept->code));
            
            if (!in_array($dept->code, ['SPI', 'DIREKSI'])) {
                $this->createEmployeeAndUser(
                    nik: 'MGR' . $nikCounter++,
                    name: 'Manager ' . $dept->name,
                    position: 'Manager',
                    dept: $dept,
                    role: Role::ManagerDept,
                    username: 'mgr_' . $code,
                    password: $password
                );

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
        }

        $deptSpi = Department::where('code', 'SPI')->first();
        if ($deptSpi) {
            $this->createEmployeeAndUser('ADM_SPI', 'Admin SPI', 'Admin', $deptSpi, Role::AdminSpi, 'admin_spi', $password);
            $this->createEmployeeAndUser('KPL_SPI', 'Kepala SPI', 'Kepala', $deptSpi, Role::Kepala_spi, 'kepala_spi', $password);
        }

        $deptIa = Department::where('code', 'IA')->first();
        if ($deptIa) {
            $this->createEmployeeAndUser('INT_AUD', 'Internal Audit', 'Internal Audit', $deptIa, Role::InternalAudit, 'internal_audit', $password);
            $this->createEmployeeAndUser('MGR_IA', 'Manager IA', 'VP Internal Audit', $deptIa, Role::ManagerIa, 'manager_ia', $password);
            $this->createEmployeeAndUser('MGR_IA_AUD', 'Manager IA (Auditee)', 'Manager', $deptIa, Role::ManagerDept, 'manager_ia_auditee', $password);
            $this->createEmployeeAndUser('PIC_IA', 'PIC IA', 'Staff', $deptIa, Role::StaffDept, 'pic_ia', $password);
        }

        $deptDir = Department::where('code', 'DIREKSI')->first();
        if ($deptDir) {
            $this->createEmployeeAndUser('DIR', 'Direksi', 'Direktur', $deptDir, Role::Direksi, 'direksi', $password);
        }

        $deptSpi = Department::where('code', 'SPI')->first();
        if ($deptSpi) {
            $this->createEmployeeAndUser('SUP_ADM', 'Super Admin', 'Super Admin', $deptSpi, Role::SuperAdmin, 'superadmin', $password);
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