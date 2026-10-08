<?php

namespace App\Enums;

enum FindingStatus: string
{
    case Draft = 'DRAFT';
    case Terdaftar = 'TERDAFTAR';
    case ProsessTindakLanjut = 'PROSES_TINDAK_LANJUT';
    case ReviewSpi = 'REVIEW_SPI';
    case MenungguStatusEksternal = 'MENUNGGU_STATUS_EKSTERNAL';
    case Closed = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Terdaftar => 'Terdaftar',
            self::ProsessTindakLanjut => 'Proses Tindak Lanjut',
            self::ReviewSpi => 'Review SPI',
            self::MenungguStatusEksternal => 'Menunggu Status Eksternal',
            self::Closed => 'Closed',
        };
    }
}
