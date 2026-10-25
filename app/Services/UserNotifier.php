<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\AdminAlert;

/**
 * Sends a bell notification to one marketplace user (buyer or seller), honouring their
 * in-app opt-out. Failures are reported, never thrown, so they can't undo the action
 * that triggered them.
 */
class UserNotifier
{
    public function notify(User $user, string $kind, string $title, ?string $url = null): void
    {
        if (NotificationPreference::where('user_id', $user->id)->where('in_app', false)->exists()) {
            return;
        }

        rescue(fn () => $user->notify(new AdminAlert($kind, $title, $url)), report: true);
    }
}
