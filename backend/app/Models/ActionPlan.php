<?php

namespace App\Models;

use App\Enums\ActionPlanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActionPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'finding_department_id',
        'title',
        'description',
        'weight',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'due_date',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'status' => ActionPlanStatus::class,
        'approved_at' => 'datetime',
        'due_date' => 'date',
    ];

    public function findingDepartment()
    {
        return $this->belongsTo(FindingDepartment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function documents()
    {
        return $this->hasMany(ActionPlanDocument::class);
    }
}
