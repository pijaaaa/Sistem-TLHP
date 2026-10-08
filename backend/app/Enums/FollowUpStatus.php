<?php

namespace App\Enums;

enum FollowUpStatus: string
{
    case Draft = 'DRAFT';
    case Diajukan = 'DIAJUKAN';
    case Revisi = 'REVISI';
    case Ditolak = 'DITOLAK';
    case Disetujui = 'DISETUJUI';
    case MenungguPersetujuanSelesai = 'MENUNGGU_PERSETUJUAN_SELESAI';
    case Selesai = 'SELESAI';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Diajukan => 'Diajukan',
            self::Revisi => 'Revisi',
            self::Ditolak => 'Ditolak',
            self::Disetujui => 'Disetujui',
            self::MenungguPersetujuanSelesai => 'Menunggu Persetujuan Selesai',
            self::Selesai => 'Selesai',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::Revisi;
    }

    public function isVisibleToMonitor(): bool
    {
        return in_array($this, [self::Disetujui, self::MenungguPersetujuanSelesai, self::Selesai], true);
    }

    public function isActiveWeight(): bool
    {
        return $this !== self::Ditolak;
    }
}