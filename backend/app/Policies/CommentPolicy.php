<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Only the comment author can edit or delete it.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->user_id === $user->id) {
            return true;
        }

        // Project owner/admin can also moderate comments.
        $project = $comment->task->project;

        return $project->owner_id === $user->id
            || $project->members()
                ->where('user_id', $user->id)
                ->wherePivot('role', 'admin')
                ->exists();
    }
}
