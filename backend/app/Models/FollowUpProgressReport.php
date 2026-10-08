<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowUpProgressReport extends Model
{
    protected $fillable = [
        'follow_up_id',
        'progress_value',
        'note',
        'reported_by',
        'reported_at',
    ];

    protected $casts = [
        'progress_value' => 'integer',
        'reported_at' => 'datetime',
    ];

    public function followUp()
    {
        return $this->belongsTo(FollowUp::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}