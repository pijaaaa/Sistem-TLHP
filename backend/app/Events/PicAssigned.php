<?php

namespace App\Events;

use App\Models\ActionPlan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PicAssigned
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<int>  $userIds  PIC yang baru ditambahkan pada penunjukan ini.
     */
    public function __construct(public ActionPlan $actionPlan, public array $userIds = [])
    {
    }
}
