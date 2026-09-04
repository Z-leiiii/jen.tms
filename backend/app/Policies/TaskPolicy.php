<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Any project member (or the owner) can view tasks in that project.
     */
    public function view(User $user, Task $task): bool
    {
        return $this->isProjectMember($user, $task);
    }

    /**
     * Any project member can create tasks — checked against the project_id
     * in the request, so this only covers the route gate; the controller
     * additionally verifies membership on the target project.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Any project member can update a task (reassign, edit, change status).
     * Tighten this to admin/owner-only if you want stricter control.
     */
    public function update(User $user, Task $task): bool
    {
        return $this->isProjectMember($user, $task);
    }

    /**
     * Only the task creator, an assignee, or a project admin/owner can delete.
     */
    public function delete(User $user, Task $task): bool
    {
        if ($task->created_by === $user->id || $task->assigned_to === $user->id) {
            return true;
        }

        return $task->project->owner_id === $user->id
            || $task->project->members()
                ->where('user_id', $user->id)
                ->wherePivot('role', 'admin')
                ->exists();
    }

    private function isProjectMember(User $user, Task $task): bool
    {
        return $task->project->owner_id === $user->id
            || $task->project->members()->where('user_id', $user->id)->exists();
    }
}
