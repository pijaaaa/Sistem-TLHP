<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvidenceFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'evidence_submission_id',
        'name',
        'path',
        'mime',
        'size',
        'label',
    ];

    public function submission()
    {
        return $this->belongsTo(EvidenceSubmission::class, 'evidence_submission_id');
    }

    public function downloadUrl(): string
    {
        return route('evidence-files.download', $this->id);
    }
}