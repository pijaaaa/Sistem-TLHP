<?php

namespace App\Events;

use App\Models\ActionPlan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SpiReviewCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public ActionPlan $actionPlan, public bool $revised)
    {
    }
}