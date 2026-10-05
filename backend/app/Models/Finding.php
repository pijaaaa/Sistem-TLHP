<?php

namespace App\Models;

use App\Enums\FindingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Finding extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'title',
        'finding_date',
        'severity',
        'recommendation',
        'auditor_action_plan',
        'status',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'finding_date' => 'date',
        'status' => FindingStatus::class,
        'is_active' => 'boolean',
    ];

    public function isEditableByAdmin(): bool
    {
        return $this->status->isEditableByAdmin();
    }

    public function documents()
    {
        return $this->hasMany(FindingDocument::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
