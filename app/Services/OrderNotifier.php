<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AdminAlert;

class OrderNotifier
{
    public function __construct(private AdminNotifier $admins) {}

    public function orderPaid(Order $order): void
    {
        $order->loadMissing(['user', 'items']);

        $this->admins->notify('order', 'New paid order '.$order->number.' — $'.number_format((float) $order->total, 2), route('admin.orders.index'));

        if ($order->user) {
            $this->notifyUser($order->user, 'Order '.$order->number.' is confirmed', route('purchases.show', $order));
        }

        $sellerIds = $order->items->pluck('seller_id')->filter()->unique();
        foreach (User::whereIn('id', $sellerIds)->get() as $seller) {
            $this->notifyUser($seller, 'New sale in order '.$order->number, route('seller.sales'));
        }
    }

    private function notifyUser(User $user, string $title, string $url): void
    {
        if (NotificationPreference::where('user_id', $user->id)->where('in_app', false)->exists()) {
            return;
        }

        rescue(fn () => $user->notify(new AdminAlert('order', $title, $url)), report: true);
    }
}
