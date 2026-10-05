<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActionPlanDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'action_plan_id',
        'name',
        'path',
        'mime',
        'size',
        'label',
        'uploaded_by',
    ];

    public function actionPlan()
    {
        return $this->belongsTo(ActionPlan::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function downloadUrl(): string
    {
        return route('action-plan-documents.download', $this->id);
    }
}
