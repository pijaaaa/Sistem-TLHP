<?php

namespace App\Models;

use App\Enums\FindingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Finding extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'registration_number',
        'source',
        'source_name',
        'lhp_number',
        'lhp_date',
        'finding_date',
        'response_period_start',
        'response_period_end',
        'fiscal_year',
        'scope',
        'title',
        'status',
        'activated_at',
        'closed_at',
        'closed_by',
        'created_by',
    ];

    protected $casts = [
        'lhp_date' => 'date',
        'finding_date' => 'date',
        'response_period_start' => 'date',
        'response_period_end' => 'date',
        'status' => FindingStatus::class,
        'activated_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function getAgeDaysAttribute(): int
    {
        return $this->finding_date ? now()->diffInDays($this->finding_date) : 0;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function auditee_departments()
    {
        return $this->belongsToMany(Department::class, 'finding_auditee_departments');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}

