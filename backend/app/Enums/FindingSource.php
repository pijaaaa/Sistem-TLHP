<?php

namespace App\Enums;

enum FindingSource: string
{
    case Bpk = 'BPK';
    case Bpkp = 'BPKP';
    case Kap = 'KAP';
    case Lainnya = 'LAINNYA';

    public function label(): string
    {
        return match ($this) {
            self::Bpk => 'BPK',
            self::Bpkp => 'BPKP',
            self::Kap => 'KAP',
            self::Lainnya => 'Lainnya',
        };
    }
}
