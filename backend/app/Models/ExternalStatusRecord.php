<?php

namespace App\Models;

use App\Enums\ExternalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExternalStatusRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'finding_id',
        'status',
        'note',
        'recorded_by',
        'recorded_at',
    ];

    protected $casts = [
        'status' => ExternalStatus::class,
        'recorded_at' => 'datetime',
    ];

    public function finding()
    {
        return $this->belongsTo(Finding::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function actionPlans()
    {
        return $this->belongsToMany(ActionPlan::class, 'external_status_action_plans', 'record_id', 'action_plan_id')
            ->withTimestamps();
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}