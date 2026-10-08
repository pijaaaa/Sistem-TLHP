<?php

namespace App\Models;

use App\Enums\ActionPlanStatus;
use App\Enums\RiskLevel;
use App\Scopes\ActionPlanVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActionPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'finding_id',
        'department_id',
        'code',
        'title',
        'condition',
        'criteria',
        'cause',
        'impact',
        'risk',
        'deadline',
        'loss_idr',
        'loss_usd',
        'status',
        'current_revision',
        'progress',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'deadline' => 'date',
        'sent_at' => 'datetime',
        'status' => ActionPlanStatus::class,
        'risk' => RiskLevel::class,
        'current_revision' => 'integer',
        'progress' => 'decimal:2',
        'loss_idr' => 'decimal:0',
        'loss_usd' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new ActionPlanVisibility());
    }

    public function finding()
    {
        return $this->belongsTo(Finding::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'action_plan_assignees', 'action_plan_id', 'user_id')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function follow_ups()
    {
        return $this->hasMany(FollowUp::class);
    }
}
