<?php

namespace App\Enums;

enum ReminderType: string
{
    case HMinus = 'H_MINUS';
    case LateManager = 'LATE_MANAGER';
    case LateKepala = 'LATE_KEPALA';
    case Unopened = 'UNOPENED';

    public function label(): string
    {
        return match ($this) {
            self::HMinus => 'Pengingat tenggat',
            self::LateManager => 'Eskalasi keterlambatan ke manager',
            self::LateKepala => 'Eskalasi keterlambatan ke Kepala SPI',
            self::Unopened => 'Pengingat belum dibuka',
        };
    }
}