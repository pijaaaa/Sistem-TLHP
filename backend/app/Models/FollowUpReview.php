<?php

namespace App\Models;

use App\Enums\ReviewDecision;
use Illuminate\Database\Eloquent\Model;

class FollowUpReview extends Model
{
    protected $fillable = [
        'follow_up_id',
        'reviewer_id',
        'decision',
        'note',
        'weight_before',
        'weight_after',
    ];

    protected $casts = [
        'decision' => ReviewDecision::class,
        'weight_before' => 'integer',
        'weight_after' => 'integer',
    ];

    public function followUp()
    {
        return $this->belongsTo(FollowUp::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}