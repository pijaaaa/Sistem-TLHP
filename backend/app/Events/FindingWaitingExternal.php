<?php

namespace App\Events;

use App\Models\Finding;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FindingWaitingExternal
{
    use Dispatchable, SerializesModels;

    public function __construct(public Finding $finding)
    {
    }
}