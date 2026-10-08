<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case AdminSpi = 'admin_spi';
    case InternalAudit = 'internal_audit';
    case ManagerDept = 'manager_dept';
    case StaffDept = 'staff_dept';
    case Kepala_spi = 'kepala_spi';
    case ManagerIa = 'manager_ia';
    case Direksi = 'direksi';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::AdminSpi => 'Admin SPI',
            self::InternalAudit => 'Internal Audit',
            self::ManagerDept => 'Manager Departemen',
            self::StaffDept => 'Staff Departemen / PIC',
            self::Kepala_spi => 'Kepala SPI',
            self::ManagerIa => 'Manager Internal Audit',
            self::Direksi => 'Direksi',
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function isMonitor(): bool
    {
        return in_array($this, [self::AdminSpi, self::InternalAudit, self::Kepala_spi, self::ManagerIa, self::Direksi], true);
    }

    public function isReadOnlyMonitor(): bool
    {
        return in_array($this, [self::ManagerIa, self::Direksi], true);
    }
}