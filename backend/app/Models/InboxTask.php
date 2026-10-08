<?php

namespace App\Models;

use App\Enums\InboxTaskStatus;
use App\Enums\InboxTaskType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InboxTask extends Model
{
    protected $fillable = [
        'recipient_id',
        'task_type',
        'subject_type',
        'subject_id',
        'title',
        'received_at',
        'first_opened_at',
        'acted_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'task_type' => InboxTaskType::class,
        'status' => InboxTaskStatus::class,
        'received_at' => 'datetime',
        'first_opened_at' => 'datetime',
        'acted_at' => 'datetime',
    ];

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}