<?php

namespace App\Events;

use App\Enums\ReviewDecision;
use App\Models\FollowUp;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FollowUpDecided
{
    use Dispatchable, SerializesModels;

    public function __construct(public FollowUp $followUp, public ReviewDecision $decision)
    {
    }
}