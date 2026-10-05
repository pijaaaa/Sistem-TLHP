<?php

namespace App\Enums;

enum AssessmentStatus: string
{
    case Ssr = 'ssr';
    case Bsr = 'bsr';
    case BelumDitindaklanjuti = 'belum_ditindaklanjuti';
    case TidakDapatDitindaklanjuti = 'tidak_dapat_ditindaklanjuti';

    public function label(): string
    {
        return match ($this) {
            self::Ssr => 'Sudah Selesai dan Direkomendasikan (SSR)',
            self::Bsr => 'Belum Selesai, Perlu Revisi (BSR)',
            self::BelumDitindaklanjuti => 'Belum Ditindaklanjuti',
            self::TidakDapatDitindaklanjuti => 'Tidak Dapat Ditindaklanjuti',
        };
    }

    public function requiresReason(): bool
    {
        return $this === self::TidakDapatDitindaklanjuti;
    }

    public function startsNewRound(): bool
    {
        return $this === self::Bsr;
    }
}