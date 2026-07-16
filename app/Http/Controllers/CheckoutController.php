<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\PaymentFulfillmentService;
use App\Services\PaymentGatewayManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function store(CheckoutService $checkout, PaymentGatewayManager $gateways): RedirectResponse
    {
        $cart = auth()->user()->cart()->firstOrCreate([], ['currency' => 'USD']);
        $data = request()->validate(['payment_provider' => ['nullable', 'string', Rule::in($gateways->availableKeysFor($cart->currency))]]);
        $result = $checkout->start($cart, auth()->user(), $data['payment_provider'] ?? null);

        return redirect()->away($result['url']);
    }

    public function success(Order $order, PaymentGatewayManager $gateways, PaymentFulfillmentService $fulfillment): View
    {
        abort_unless($order->user_id === auth()->id(), 403);
        // Confirm synchronously on return: ask the provider's API whether the order is paid and
        // fulfil right away instead of leaving the buyer staring at "confirming" until the webhook
        // lands. fulfill() is idempotent, so a webhook arriving later (or first) changes nothing.
        if ($order->payment_status !== 'paid' && $order->payment_provider) {
            try {
                if ($confirmed = $gateways->driver($order->payment_provider)->verifyReturn($order)) {
                    $fulfillment->fulfill($order->id, $confirmed['payment_id'], $order->payment_provider, $confirmed['payload'] ?? []);
                    $order->refresh();
                }
            } catch (\Throwable $e) {
                // Verification is best-effort; the page still renders and the webhook remains the fallback.
                report($e);
            }
        }

        return view('checkout.success', compact('order'));
    }

    /** Manual-transfer buyers land here instead of a provider; the order stays unpaid until an admin confirms. */
    public function bankTransfer(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 403);
        abort_unless($order->payment_provider === 'bank_transfer', 404);

        return view('checkout.bank-transfer', ['order' => $order, 'instructions' => (string) config('services.bank_transfer.instructions')]);
    }
}
