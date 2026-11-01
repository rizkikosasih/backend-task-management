<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProjectService
{
    public function getProjects(array $filters): LengthAwarePaginator
    {
        if (Gate::denies('viewAny', Project::class)) {
            throw new AuthorizationException('You do not have access to any projects.');
        }

        return Project::query()
            ->when(
                $filters['search'] ?? null,
                fn($q, $search) => $q->where('name', 'like', "%{$search}%"),
            )
            ->when($filters['created_by'] ?? null, fn($q, $user) => $q->where('created_by', $user))
            ->withCount('tasks')
            ->with(['user:id,name'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();
    }

    public function getById(Project $project): Project
    {
        if (Gate::denies('view', $project)) {
            throw new AuthorizationException('You are not involved in this project details.');
        }

        return $project->load(['user:id,name'])->loadCount('tasks');
    }

    public function create(array $data): Project
    {
        if (Gate::denies('create', Project::class)) {
            throw new AuthorizationException('Only administrators can initialize new projects.');
        }

        $data['created_by'] = Auth::id();

        $project = Project::create($data);

        return $project->load(['user:id,name'])->loadCount('tasks');
    }

    public function update(Project $project, array $data): Project
    {
        if (Gate::denies('update', $project)) {
            throw new AuthorizationException(
                'Modification of project structure is restricted to administrators.',
            );
        }

        $project->update($data);

        return $project->fresh(['user:id,name'])->loadCount('tasks');
    }

    public function delete(Project $project): bool
    {
        if (Gate::denies('delete', $project)) {
            throw new AuthorizationException(
                'Modification of project structure is restricted to administrators.',
            );
        }

        return $project->delete();
    }
}
