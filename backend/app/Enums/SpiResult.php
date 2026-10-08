<?php

namespace App\Enums;

enum SpiResult: string
{
    case Sesuai = 'SESUAI';
    case Revisi = 'REVISI';

    public function label(): string
    {
        return match ($this) {
            self::Sesuai => 'Sesuai',
            self::Revisi => 'Revisi',
        };
    }
}