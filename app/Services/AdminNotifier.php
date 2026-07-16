<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Support\Facades\Notification;

/**
 * Fan a marketplace event out to every administrator's bell. Failures are swallowed —
 * a notification must never break the workflow (checkout, submission…) that raised it.
 */
class AdminNotifier
{
    public function notify(string $kind, string $title, ?string $url = null): void
    {
        rescue(function () use ($kind, $title, $url): void {
            $admins = User::whereHas('roles', fn ($q) => $q->where('slug', 'administrator'))->get();
            Notification::send($admins, new AdminAlert($kind, $title, $url));
        }, report: false);
    }
}
