<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;

class PurchaseController extends Controller
{
    public function index(): View
    {
        $orders = auth()->user()->orders()->with('items.license')->latest()->paginate(15);

        return view('purchases.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $order->load(['items.license.product', 'items.product']);
        foreach ($order->items as $item) {
            if ($item->license) {
                $item->license->download_url = URL::temporarySignedRoute('downloads.show', now()->addMinutes(10), ['license' => $item->license]);
            }
        }

return view('purchases.show', compact('order'));
    }

    /** Print-friendly invoice; buyers use the browser's print-to-PDF. */
    public function invoice(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 403);
        abort_unless(in_array($order->payment_status, ['paid', 'partially_refunded'], true), 404, 'Invoices are available once the order is paid.');
        $order->load(['items', 'user']);

        return view('purchases.invoice', compact('order'));
    }
}
