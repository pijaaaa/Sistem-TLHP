<?php

namespace App\Events;

use App\Models\FollowUpComment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IaCommentAdded
{
    use Dispatchable, SerializesModels;

    public function __construct(public FollowUpComment $comment)
    {
    }
}