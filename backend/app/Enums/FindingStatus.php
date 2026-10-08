<?php

namespace App\Enums;

enum FindingStatus: string
{
    case Draft = 'draft';
    case Terdaftar = 'terdaftar';
    case ProsessTindakLanjut = 'proses_tindak_lanjut';
    case ReviewSpi = 'review_spi';
    case MenungguStatusEksternal = 'menunggu_status_eksternal';
    case Closed = 'closed';

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
