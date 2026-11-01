<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view task');
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $task->assigned_to;
    }

    public function create(User $user, Project $project): bool
    {
        return $user->id === $project->created_by;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->project?->created_by;
    }

    public function updateStatus(User $user, Task $task): bool
    {
        if ($user->id === $task->project?->created_by) {
            return true;
        }

        return $user->id === $task->assigned_to;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->project?->created_by;
    }
}
