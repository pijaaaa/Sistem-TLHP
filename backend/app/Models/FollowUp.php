<?php

namespace App\Models;

use App\Enums\FollowUpStatus;
use App\Scopes\FollowUpVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FollowUp extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'action_plan_id',
        'revision_no',
        'description',
        'target_date',
        'weight',
        'progress',
        'status',
        'linked_follow_up_id',
        'created_by',
        'approved_by',
        'approved_at',
        'completed_at',
    ];

    protected $casts = [
        'target_date' => 'date',
        'weight' => 'integer',
        'progress' => 'integer',
        'revision_no' => 'integer',
        'status' => FollowUpStatus::class,
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new FollowUpVisibility());
    }

    public function actionPlan()
    {
        return $this->belongsTo(ActionPlan::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function linkedFollowUp()
    {
        return $this->belongsTo(FollowUp::class, 'linked_follow_up_id');
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'follow_up_assignees', 'follow_up_id', 'user_id')
            ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(FollowUpReview::class)->latest('id');
    }

    public function comments()
    {
        return $this->hasMany(FollowUpComment::class)->latest('id');
    }

    public function progress_reports()
    {
        return $this->hasMany(FollowUpProgressReport::class)->latest('id');
    }
}