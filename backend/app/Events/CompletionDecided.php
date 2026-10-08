<?php

namespace App\Events;

use App\Models\FollowUp;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompletionDecided
{
    use Dispatchable, SerializesModels;

    public function __construct(public FollowUp $followUp, public bool $approved)
    {
    }
}