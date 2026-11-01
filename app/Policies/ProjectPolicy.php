<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Menentukan apakah user bisa melihat daftar project (index).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view project');
    }

    /**
     * Menentukan apakah user bisa melihat detail project tertentu.
     */
    public function view(User $user, Project $project): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasPermissionTo('view project') &&
            $project->tasks()->where('assigned_to', $user->id)->exists();
    }

    /**
     * Menentukan apakah user bisa membuat project.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') && $user->hasPermissionTo('create project');
    }

    /**
     * Menentukan apakah user bisa mengupdate project.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('update project') && $user->id === $project->created_by;
    }

    /**
     * Menentukan apakah user bisa menghapus project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('delete project') && $user->id === $project->created_by;
    }
}
