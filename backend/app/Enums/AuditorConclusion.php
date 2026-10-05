<?php

namespace App\Enums;

enum AuditorConclusion: string
{
    case Closed = 'ditutup';
    case NeedsRevision = 'perlu_perbaikan';

    public function label(): string
    {
        return match ($this) {
            self::Closed => 'Ditutup (Closed)',
            self::NeedsRevision => 'Perlu Perbaikan (BSR)',
        };
    }

    public function closesFinding(): bool
    {
        return $this === self::Closed;
    }
}