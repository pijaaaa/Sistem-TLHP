<?php

namespace App\Enums;

enum ActionPlanStatus: string
{
    case Draft = 'DRAFT';
    case MenungguPenentuanPic = 'MENUNGGU_PENENTUAN_PIC';
    case ProsesTindakLanjut = 'PROSES_TINDAK_LANJUT';
    case DiajukanKeSpi = 'DIAJUKAN_KE_SPI';
    case RevisiSpi = 'REVISI_SPI';
    case Sesuai = 'SESUAI';
    case Closed = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::MenungguPenentuanPic => 'Menunggu Penentuan PIC',
            self::ProsesTindakLanjut => 'Proses Tindak Lanjut',
            self::DiajukanKeSpi => 'Diajukan ke SPI',
            self::RevisiSpi => 'Revisi SPI',
            self::Sesuai => 'Sesuai',
            self::Closed => 'Closed',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}
