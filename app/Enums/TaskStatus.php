<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';

    public function label(): string
    {
        return match ($this) {
            self::TODO => 'Todo',
            self::IN_PROGRESS => 'In Progress',
            self::DONE => 'Done',
        };
    }

    public function isDone(): bool
    {
        return $this === self::DONE;
    }
}
