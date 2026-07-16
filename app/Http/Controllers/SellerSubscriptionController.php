<?php

namespace App\Http\Controllers;

use App\Models\SellerSubscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SellerSubscriptionController extends Controller
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function index(): View
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->orderBy('price')->get();
        $current = $this->subscriptions->activeFor(auth()->user());
        $pending = SellerSubscription::where('user_id', auth()->id())->where('status', 'pending')->latest()->first();

        return view('seller.subscription', [
            'plans' => $plans,
            'current' => $current,
            'pending' => $pending,
            'listingCount' => $this->subscriptions->activeListingCount(auth()->user()),
        ]);
    }

    public function subscribe(SubscriptionPlan $plan): RedirectResponse
    {
        abort_unless($plan->is_active, 404);
        $subscription = $this->subscriptions->subscribe(auth()->user(), $plan);
        $message = $subscription->status === 'active'
         ? 'Your '.$plan->name.' plan is now active.'
         : 'Your '.$plan->name.' subscription is pending. It activates once your payment is confirmed.';

        return redirect()->route('seller.subscription.index')->with('status', $message);
    }

    public function cancel(SellerSubscription $subscription): RedirectResponse
    {
        abort_unless($subscription->user_id === auth()->id(), 403);
        $this->subscriptions->cancel($subscription);

        return back()->with('status', 'Your subscription has been cancelled.');
    }
}
