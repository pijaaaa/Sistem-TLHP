<?php

namespace App\Models;

use App\Enums\ReminderType;
use Illuminate\Database\Eloquent\Model;

class ReminderLog extends Model
{
    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'reminder_type',
        'sent_on',
    ];

    protected $casts = [
        'reminder_type' => ReminderType::class,
        'sent_on' => 'date',
    ];
}