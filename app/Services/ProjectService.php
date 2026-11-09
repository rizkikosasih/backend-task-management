<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProjectService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getProjects(array $filters): LengthAwarePaginator
    {
        if (Gate::denies('viewAny', Project::class)) {
            throw new AuthorizationException('You do not have access to any projects.');
        }

        $user = Auth::user();

        return Project::query()
            ->when($user && $user->hasRole('member') && !$user->hasRole('admin'), function ($q) use ($user) {
                return $q->whereHas('tasks', function ($t) use ($user) {
                    $t->where('assigned_to', $user->id);
                });
            })
            ->when(
                $filters['search'] ?? null,
                fn ($q, $search) => $q->where('name', 'like', "%{$search}%"),
            )
            ->when($filters['created_by'] ?? null, fn ($q, $user) => $q->where('created_by', $user))
            ->withCount('tasks')
            ->with(['user:id,name'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate((int) ($filters['per_page'] ?? 10))
            ->withQueryString();
    }

    public function getById(Project $project): Project
    {
        if (Gate::denies('view', $project)) {
            throw new AuthorizationException('You are not involved in this project details.');
        }

        return $project->load(['user:id,name'])->loadCount('tasks');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Project
    {
        if (Gate::denies('create', Project::class)) {
            throw new AuthorizationException('Only administrators can initialize new projects.');
        }

        $data['created_by'] = Auth::id();

        $project = Project::create($data);

        return $project->load(['user:id,name'])->loadCount('tasks');
    }

    /**
     * @param  array<string, mixed>  $data
     */
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

        return (bool) $project->delete();
    }
}
