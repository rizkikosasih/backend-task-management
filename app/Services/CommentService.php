<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CommentService
{
    public function getTaskComments(Task $task, int $perPage = 10): LengthAwarePaginator
    {
        if (Gate::denies('view', $task)) {
            throw new AuthorizationException('Access denied to comments for this task.');
        }

        return $task
            ->comments()
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function create(Task $task, string $message): Comment
    {
        if (Gate::denies('view', $task)) {
            throw new AuthorizationException(
                'You cannot comment on a task you are not involved in.',
            );
        }

        $comment = Comment::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'message' => $message,
        ]);

        return $comment->load('user:id,name');
    }

    public function delete(Comment $comment): bool
    {
        if (Gate::denies('delete', $comment)) {
            throw new AuthorizationException('You can only delete your own comments.');
        }

        return $comment->delete();
    }
}
