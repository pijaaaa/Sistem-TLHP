<?php

namespace App\Enums;

enum RevisionSource: string
{
    case SpiReview = 'SPI_REVIEW';
    case ExternalStatus = 'EXTERNAL_STATUS';

    public function label(): string
    {
        return match ($this) {
            self::SpiReview => 'Review SPI',
            self::ExternalStatus => 'Status Eksternal',
        };
    }
}