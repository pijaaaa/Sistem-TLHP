<?php

namespace App\Enums;

enum InboxTaskStatus: string
{
    case Open = 'OPEN';
    case Done = 'DONE';
}