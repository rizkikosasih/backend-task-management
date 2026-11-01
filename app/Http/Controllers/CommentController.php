<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use App\Services\CommentService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(protected CommentService $service) {}

    public function index(Request $request, Task $task)
    {
        $perPage = $request->query('per_page', 10);

        $comments = $this->service->getTaskComments($task, $perPage);

        return ApiResponse::paginated(
            $comments,
            CommentResource::class,
            'Comment list retrieved successfully',
        );
    }

    public function store(StoreCommentRequest $request, Task $task)
    {
        $comment = $this->service->create($task, $request->validated()['message']);

        return ApiResponse::success(
            new CommentResource($comment),
            'Comment created successfully',
            201,
        );
    }

    public function destroy(Comment $comment)
    {
        $this->service->delete($comment);

        return ApiResponse::success(null, 'Comment deleted successfully');
    }
}
