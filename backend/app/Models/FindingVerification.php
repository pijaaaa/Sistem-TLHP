<?php

namespace App\Models;

use App\Enums\AuditorConclusion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FindingVerification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'finding_id',
        'round',
        'auditor_result',
        'auditor_conclusion',
        'verified_date',
        'notes',
        'is_closed',
        'created_by',
    ];

    protected $casts = [
        'round' => 'integer',
        'auditor_conclusion' => AuditorConclusion::class,
        'verified_date' => 'date',
        'is_closed' => 'boolean',
    ];

    public function finding()
    {
        return $this->belongsTo(Finding::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}