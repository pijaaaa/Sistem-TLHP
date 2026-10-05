<?php

namespace App\Enums;

enum Role: string
{
    case AdminSpi   = 'admin_spi';
    case ManagerIa    = 'manager_ia';
    case ManagerDept  = 'manager_dept';
    case StaffDept    = 'staff_dept';
    case ManagerSpi   = 'manager_spi';
    case SuperAdmin   = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::AdminSpi    => 'Admin SPI',
            self::ManagerIa   => 'Manager IA',
            self::ManagerDept => 'Manager Departemen',
            self::StaffDept   => 'Staff Departemen / PIC',
            self::ManagerSpi  => 'Manager SPI',
            self::SuperAdmin  => 'Super Admin',
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function isManager(): bool
    {
        return in_array($this, [self::ManagerIa, self::ManagerDept, self::ManagerSpi], true);
    }

    public function isAdminSpi(): bool
    {
        return $this === self::AdminSpi;
    }
}