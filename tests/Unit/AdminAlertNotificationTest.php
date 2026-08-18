<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\AdminAlert;
use PHPUnit\Framework\TestCase;

class AdminAlertNotificationTest extends TestCase
{
    public function test_admin_alert_uses_database_and_broadcast_channels(): void
    {
        $notification = new AdminAlert('order', 'New paid order DM-1001', '/admin/orders');

        $this->assertSame(['database', 'broadcast'], $notification->via(new User));
        $this->assertSame('admin.alert', $notification->broadcastType());
    }

    public function test_admin_alert_broadcast_payload_targets_broadcasts_queue(): void
    {
        $notification = new AdminAlert('review', 'Product submitted for review', '/admin/products/review');
        $message = $notification->toBroadcast(new User);

        $this->assertSame('broadcasts', $message->queue);
        $this->assertSame('review', $message->data['kind']);
        $this->assertSame('Product submitted for review', $message->data['title']);
        $this->assertSame('/admin/products/review', $message->data['url']);
        $this->assertArrayHasKey('created_at', $message->data);
    }
}
