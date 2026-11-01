<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\Project\IndexProjectRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;

class ProjectController extends Controller
{
    public function __construct(protected ProjectService $service) {}

    public function index(IndexProjectRequest $request)
    {
        $projects = $this->service->getProjects($request->validated());

        return ApiResponse::paginated(
            $projects,
            ProjectResource::class,
            'Project list retrieved successfully',
        );
    }

    public function store(StoreProjectRequest $request)
    {
        $project = $this->service->create($request->validated());

        return ApiResponse::success(
            new ProjectResource($project),
            'Project created successfully',
            201,
        );
    }

    public function show(Project $project)
    {
        $project = $this->service->getById($project);

        return ApiResponse::success(
            new ProjectResource($project),
            'Project retrieved successfully',
        );
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $project = $this->service->update($project, $request->validated());

        return ApiResponse::success(new ProjectResource($project), 'Project updated successfully');
    }

    public function destroy(Project $project)
    {
        $this->service->delete($project);

        return ApiResponse::success(null, 'Project deleted successfully');
    }
}
