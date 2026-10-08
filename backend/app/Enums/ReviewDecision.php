<?php

namespace App\Enums;

enum ReviewDecision: string
{
    case Setujui = 'SETUJUI';
    case Revisi = 'REVISI';
    case Tolak = 'TOLAK';
    case KembaliRevisi = 'KEMBALI_REVISI';
    case OverrideBobot = 'OVERRIDE_BOBOT';
    case Selesaikan = 'SELESAI';
    case RevisiSelesai = 'REVISI_SELESAI';

    public function label(): string
    {
        return match ($this) {
            self::Setujui => 'Setujui',
            self::Revisi => 'Revisi',
            self::Tolak => 'Tolak',
            self::KembaliRevisi => 'Kembalikan ke Revisi',
            self::OverrideBobot => 'Override Bobot',
            self::Selesaikan => 'Setujui Selesai',
            self::RevisiSelesai => 'Minta Revisi Penyelesaian',
        };
    }
}