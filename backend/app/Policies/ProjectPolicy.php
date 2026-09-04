<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Any authenticated user can view the list of projects they belong to
     * (the controller/service scopes the query — this just gates the route).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Owner or any member (of any role) can view project details.
     */
    public function view(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id
            || $project->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Any authenticated user can create a project — they become the owner.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Owner or a member with the 'admin' role can update project details.
     */
    public function update(User $user, Project $project): bool
    {
        if ($project->owner_id === $user->id) {
            return true;
        }

        return $project->members()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    /**
     * Only the owner can delete a project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id;
    }

    /**
     * Owner or admin-role member can add/remove members.
     */
    public function manageMembers(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }
}
