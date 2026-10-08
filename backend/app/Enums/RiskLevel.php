<?php

namespace App\Enums;

enum RiskLevel: string
{
    case Rendah = 'RENDAH';
    case Sedang = 'SEDANG';
    case Tinggi = 'TINGGI';
    case Kritis = 'KRITIS';

    public function label(): string
    {
        return match ($this) {
            self::Rendah => 'Rendah',
            self::Sedang => 'Sedang',
            self::Tinggi => 'Tinggi',
            self::Kritis => 'Kritis',
        };
    }
}
