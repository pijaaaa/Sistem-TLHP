<?php

namespace App\Enums;

enum FindingStatus: string
{
    case Draft = 'draft';
    case SentToIa = 'dikirim_ke_ia';
    case Distributed = 'didistribusikan';
    case InProgress = 'dalam_proses';
    case PendingIaAssessment = 'menunggu_assessment_ia';
    case Closed = 'closed';
    case CaseClosed = 'case_closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::SentToIa => 'Dikirim ke IA',
            self::Distributed => 'Didistribusikan',
            self::InProgress => 'Dalam Proses',
            self::PendingIaAssessment => 'Menunggu Assessment IA',
            self::Closed => 'Closed',
            self::CaseClosed => 'Tidak Dapat Ditindaklanjuti',
        };
    }

    public function isEditableByAdmin(): bool
    {
        return in_array($this, [self::Draft, self::SentToIa], true);
    }
}
