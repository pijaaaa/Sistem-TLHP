<?php

namespace App\Models;

use App\Enums\SpiResult;
use Illuminate\Database\Eloquent\Model;

class SpiReview extends Model
{
    protected $fillable = [
        'action_plan_id',
        'revision_no',
        'reviewer_id',
        'completed_at',
    ];

    protected $casts = [
        'revision_no' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function actionPlan()
    {
        return $this->belongsTo(ActionPlan::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function items()
    {
        return $this->hasMany(SpiReviewItem::class);
    }
}