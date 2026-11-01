<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TaskStatus;
use App\Events\TaskAssigned;
use App\Exceptions\BusinessException;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TaskService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getTasks(array $filters): LengthAwarePaginator
    {
        if (Gate::denies('viewAny', Task::class)) {
            throw new AuthorizationException('Access denied to task list.');
        }

        $user = Auth::user();

        return Task::query()
            ->when($user && $user->hasRole('member'), function ($q) use ($user) {
                return $q->where('assigned_to', $user->id);
            })
            ->when(
                $filters['search'] ?? null,
                fn ($q, $search) => $q->where('title', 'like', "%{$search}%"),
            )
            ->when(
                $filters['project_id'] ?? null,
                fn ($q, $projectId) => $q->where('project_id', $projectId),
            )
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when(
                $filters['assigned_to'] ?? null,
                fn ($q, $assignedTo) => $q->where('assigned_to', $assignedTo),
            )
            ->with(['project:id,name', 'user:id,name'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate((int) ($filters['per_page'] ?? 10))
            ->withQueryString();
    }

    public function getById(Task $task): Task
    {
        if (Gate::denies('view', $task)) {
            throw new AuthorizationException('Access denied to task details.');
        }

        return $task->load(['user:id,name', 'project:id,name']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Task
    {
        $project = Project::findOrFail($data['project_id']);

        if (Gate::denies('create', [Task::class, $project])) {
            throw new AuthorizationException('Tasks can only be added by the project owner.');
        }

        $data['status'] = isset($data['status'])
            ? TaskStatus::from((string) $data['status'])
            : TaskStatus::TODO;

        $task = Task::create($data);

        if (! empty($data['assigned_to'])) {
            TaskAssigned::dispatch($task);
        }

        return $task->load(['project:id,name', 'user:id,name']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Task $task, array $data): Task
    {
        if (Gate::denies('update', $task)) {
            throw new AuthorizationException('Only the project owner can update task details.');
        }

        $task->update($data);

        return $task->fresh(['project:id,name', 'user:id,name']);
    }

    public function updateStatus(Task $task, string $status): Task
    {
        if (Gate::denies('updateStatus', $task)) {
            throw new AuthorizationException('You are not authorized to update this task status.');
        }

        return DB::transaction(function () use ($task, $status): Task {
            $lockedTask = Task::where('id', $task->id)->lockForUpdate()->firstOrFail();

            $newStatus = TaskStatus::from($status);

            if ($lockedTask->status->isDone() && ! $newStatus->isDone()) {
                throw new BusinessException(
                    message: 'Invalid Task Transition',
                    errors: [
                        'status' => 'Done tasks are locked and cannot be reverted to a previous state.',
                    ],
                );
            }

            if ($lockedTask->status === $newStatus) {
                return $lockedTask;
            }

            $lockedTask->update(['status' => $newStatus]);

            return $lockedTask->fresh(['project:id,name', 'user:id,name']);
        });
    }

    public function delete(Task $task): bool
    {
        if (Gate::denies('delete', $task)) {
            throw new AuthorizationException('Task removal is restricted to the project owner.');
        }

        return (bool) $task->delete();
    }
}
