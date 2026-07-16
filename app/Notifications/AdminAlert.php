<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * One generic database notification for the admin bell. `kind` picks the icon,
 * `url` is where opening the notification lands.
 */
class AdminAlert extends Notification
{
    public function __construct(public string $kind, public string $title, public ?string $url = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string,mixed> */
    public function toDatabase(object $notifiable): array
    {
        return ['kind' => $this->kind, 'title' => $this->title, 'url' => $this->url];
    }
}
