<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    /**
     * The uploader, or the project owner/admin, can delete an attachment.
     */
    public function delete(User $user, Attachment $attachment): bool
    {
        if ($attachment->uploaded_by === $user->id) {
            return true;
        }

        $project = $attachment->task->project;

        return $project->owner_id === $user->id
            || $project->members()
                ->where('user_id', $user->id)
                ->wherePivot('role', 'admin')
                ->exists();
    }
}
