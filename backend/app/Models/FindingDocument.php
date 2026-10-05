<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FindingDocument extends Model
{
    protected $fillable = [
        'finding_id',
        'name',
        'path',
        'mime',
        'size',
        'label',
        'uploaded_by',
    ];

    public function finding()
    {
        return $this->belongsTo(Finding::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
