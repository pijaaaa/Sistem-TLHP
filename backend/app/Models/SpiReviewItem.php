<?php

namespace App\Models;

use App\Enums\SpiResult;
use Illuminate\Database\Eloquent\Model;

class SpiReviewItem extends Model
{
    protected $fillable = [
        'spi_review_id',
        'follow_up_id',
        'result',
        'note',
    ];

    protected $casts = [
        'result' => SpiResult::class,
    ];

    public function spiReview()
    {
        return $this->belongsTo(SpiReview::class);
    }

    public function followUp()
    {
        return $this->belongsTo(FollowUp::class);
    }
}