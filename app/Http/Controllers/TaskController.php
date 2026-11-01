<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\Task\IndexTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;

class TaskController extends Controller
{
    public function __construct(protected TaskService $service) {}

    public function index(IndexTaskRequest $request)
    {
        $tasks = $this->service->getTasks($request->validated());

        return ApiResponse::paginated(
            $tasks,
            TaskResource::class,
            'Task list retrieved successfully',
        );
    }

    public function store(StoreTaskRequest $request)
    {
        $task = $this->service->create($request->validated());

        return ApiResponse::success(new TaskResource($task), 'Task created successfully', 201);
    }

    public function show(Task $task)
    {
        $task = $this->service->getById($task);

        return ApiResponse::success(new TaskResource($task), 'Task retrieved successfully');
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task = $this->service->update($task, $request->validated());

        return ApiResponse::success(new TaskResource($task), 'Task updated successfully');
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task)
    {
        $status = $request->validated()['status'];

        $task = $this->service->updateStatus($task, $status);

        return ApiResponse::success(new TaskResource($task), 'Task status updated successfully');
    }

    public function destroy(Task $task)
    {
        $this->service->delete($task);

        return ApiResponse::success(null, 'Task deleted successfully');
    }
}
