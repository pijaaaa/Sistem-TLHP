<?php

namespace App\Models;

use App\Enums\CommentKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FollowUpComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'follow_up_id',
        'author_id',
        'kind',
        'body',
    ];

    protected $casts = [
        'kind' => CommentKind::class,
    ];

    public function followUp()
    {
        return $this->belongsTo(FollowUp::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}