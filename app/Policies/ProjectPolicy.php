<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view project');
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasPermissionTo('view project') &&
            $project->tasks()->where('assigned_to', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') && $user->hasPermissionTo('create project');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('update project') && $user->id === $project->created_by;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('delete project') && $user->id === $project->created_by;
    }
}
