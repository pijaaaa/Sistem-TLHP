<?php

namespace App\Enums;

enum CommentKind: string
{
    case Diskusi = 'DISKUSI';
    case IaComment = 'IA_COMMENT';

    public function label(): string
    {
        return match ($this) {
            self::Diskusi => 'Diskusi',
            self::IaComment => 'Komentar IA',
        };
    }
}