<?php

namespace App\Listeners;

use App\Services\TaskDispatcher;

class DispatchNotificationTasks
{
    public function handle(object $event): void
    {
        $this->__invoke($event);
    }

    public function __invoke(object $event): void
    {
        TaskDispatcher::dispatchEvent($event);
    }
}