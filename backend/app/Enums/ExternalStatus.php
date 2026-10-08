<?php

namespace App\Enums;

enum ExternalStatus: string
{
    case Ssr = 'SSR';
    case Bsr = 'BSR';
    case Bd = 'BD';
    case Tdtl = 'TDTL';

    public function label(): string
    {
        return match ($this) {
            self::Ssr => 'SSR — Sudah Selesai & Direkomendasikan',
            self::Bsr => 'BSR — Belum Selesai, Perlu Revisi',
            self::Bd => 'BD — Belum Ditindaklanjuti',
            self::Tdtl => 'TDTL — Tidak Dapat Ditindaklanjuti',
        };
    }

    public function isClosing(): bool
    {
        return $this === self::Ssr;
    }
}