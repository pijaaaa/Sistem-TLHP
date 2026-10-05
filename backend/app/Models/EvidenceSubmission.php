<?php

namespace App\Models;

use App\Enums\EvidenceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvidenceSubmission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'action_plan_id',
        'status',
        'submitted_by',
        'reviewed_by',
        'reviewed_at',
        'revision_note',
    ];

    protected $casts = [
        'status' => EvidenceStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function actionPlan()
    {
        return $this->belongsTo(ActionPlan::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function files()
    {
        return $this->hasMany(EvidenceFile::class);
    }
}