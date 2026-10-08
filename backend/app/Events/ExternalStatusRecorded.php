<?php

namespace App\Events;

use App\Enums\ExternalStatus;
use App\Models\Finding;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExternalStatusRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(public Finding $finding, public ExternalStatus $status)
    {
    }
}