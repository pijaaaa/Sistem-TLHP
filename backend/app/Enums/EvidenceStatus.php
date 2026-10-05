<?php

namespace App\Enums;

enum EvidenceStatus: string
{
    case Diajukan = 'diajukan';
    case Disetujui = 'disetujui';
    case Revisi = 'revisi';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Diajukan',
            self::Disetujui => 'Disetujui',
            self::Revisi => 'Revisi',
        };
    }
}