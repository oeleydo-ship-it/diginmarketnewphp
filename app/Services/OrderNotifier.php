<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;

class OrderNotifier
{
    public function __construct(private AdminNotifier $admins, private UserNotifier $users) {}

    public function orderPaid(Order $order): void
    {
        $order->loadMissing(['user', 'items']);

        $this->admins->notify('order', 'New paid order '.$order->number.' — $'.number_format((float) $order->total, 2), route('admin.orders.index'));

        if ($order->user && Setting::enabled('notifications.order_buyer.app')) {
            $this->notifyUser($order->user, 'Order '.$order->number.' is confirmed', route('purchases.show', $order));
        }

        if (! Setting::enabled('notifications.order_seller.app')) {
            return;
        }
        $sellerIds = $order->items->pluck('seller_id')->filter()->unique();
        foreach (User::whereIn('id', $sellerIds)->get() as $seller) {
            $this->notifyUser($seller, 'New sale in order '.$order->number, route('seller.sales'));
        }
    }

    private function notifyUser(User $user, string $title, string $url): void
    {
        $this->users->notify($user, 'order', $title, $url);
    }
}
