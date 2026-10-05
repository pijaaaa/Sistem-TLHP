<?php

namespace App\Enums;

enum FindingDepartmentStatus: string
{
    case Received = 'diterima';
    case PicAssigned = 'pic_ditugaskan';
    case InProgress = 'dalam_proses';
    case Complete100 = 'selesai_100';
    case ForwardedToIa = 'diteruskan_ke_ia';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Diterima',
            self::PicAssigned => 'PIC Di-tugaskan',
            self::InProgress => 'Dalam Proses',
            self::Complete100 => 'Selesai 100%',
            self::ForwardedToIa => 'Diteruskan ke IA',
        };
    }

    public function isEditableByManager(): bool
    {
        return in_array($this, [self::Received, self::PicAssigned], true);
    }
}
