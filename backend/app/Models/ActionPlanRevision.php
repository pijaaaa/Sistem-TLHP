<?php

namespace App\Models;

use App\Enums\RevisionSource;
use Illuminate\Database\Eloquent\Model;

class ActionPlanRevision extends Model
{
    protected $fillable = [
        'action_plan_id',
        'revision_no',
        'requested_by',
        'requested_role',
        'source',
        'reason',
        'requested_at',
        'forwarded_to_pic_at',
        'new_deadline',
    ];

    protected $casts = [
        'revision_no' => 'integer',
        'source' => RevisionSource::class,
        'requested_at' => 'datetime',
        'forwarded_to_pic_at' => 'datetime',
        'new_deadline' => 'date',
    ];

    public function actionPlan()
    {
        return $this->belongsTo(ActionPlan::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}