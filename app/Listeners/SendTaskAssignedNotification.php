<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TaskAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendTaskAssignedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TaskAssigned $event): void
    {
        Log::info('Task assigned notification processed', [
            'task_id' => $event->task->id,
            'assigned_to' => $event->task->assigned_to,
        ]);
    }
}

