<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    /**
     * A notification only ever belongs to (and is manageable by) its recipient.
     */
    public function manage(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }
}
