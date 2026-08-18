<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;

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
        $order->load(['items.license.product', 'items.product', 'items.extendedLicense']);
        foreach ($order->items as $item) {
            if (! $item->license) {
                continue;
            }
            // Offer every version the licence is entitled to — the newest by default — so buyers
            // actually receive the free updates the seller ships after their purchase.
            $versions = $item->license->downloadableVersions();
            $item->license->download_url = DownloadController::signedUrl($item->license);
            $item->license->available_versions = $versions->map(fn ($version) => ['version' => $version, 'url' => DownloadController::signedUrl($item->license, $version), 'is_purchased' => (int) $version->id === (int) $item->license->product_version_id]);
            $item->license->latest_version = $versions->first();
            $item->license->has_update = $versions->first() !== null && (int) $versions->first()->id !== (int) $item->license->product_version_id;
        }

return view('purchases.show', compact('order'));
    }

    /** Print-friendly invoice; buyers use the browser's print-to-PDF. */
    public function invoice(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 403);
        abort_unless($order->isSettled(), 404, 'Invoices are available once the order is paid.');
        $order->load(['items', 'user']);

        return view('purchases.invoice', compact('order'));
    }
}
