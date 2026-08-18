<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;
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
        return ['database', 'broadcast'];
    }

    /** @return array<string,mixed> */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->payload() + [
            'created_at' => now()->toIso8601String(),
        ]))->onQueue('broadcasts');
    }

    public function broadcastType(): string
    {
        return 'admin.alert';
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    /** @return array<string,mixed> */
    private function payload(): array
    {
        return ['kind' => $this->kind, 'title' => $this->title, 'url' => $this->url];
    }
}
