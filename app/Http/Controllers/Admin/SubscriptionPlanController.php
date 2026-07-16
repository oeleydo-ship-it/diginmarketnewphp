<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SellerSubscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class SubscriptionPlanController extends Controller
{
    public function index(): View
    {
        $plans = SubscriptionPlan::withCount(['subscriptions' => fn ($q) => $q->where('status', 'active')])->orderBy('sort_order')->orderBy('price')->get();
        $pending = SellerSubscription::with(['seller', 'plan'])->where('status', 'pending')->latest()->get();

        return view('admin.subscriptions.index', compact('plans', 'pending'));
    }

    public function store(): RedirectResponse
    {
        $data = $this->validated();
        $plan = SubscriptionPlan::create($data + ['slug' => str($data['name'])->slug().'-'.str()->lower(str()->random(4))]);
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'subscription_plan.created', 'entity_type' => SubscriptionPlan::class, 'entity_id' => $plan->id, 'new_values' => $data, 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);

        return back()->with('status', 'Plan created.');
    }

    public function update(SubscriptionPlan $plan): RedirectResponse
    {
        $plan->update($this->validated());

        return back()->with('status', 'Plan updated.');
    }

    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        // Keep the historical record: retire the plan rather than deleting rows sellers still reference.
        $plan->update(['is_active' => false]);

        return back()->with('status', 'Plan deactivated.');
    }

    /** Confirm an off-platform payment and switch the seller's pending subscription on. */
    public function activate(SellerSubscription $subscription, SubscriptionService $subscriptions): RedirectResponse
    {
        abort_unless($subscription->status === 'pending', 422, 'This subscription is not awaiting activation.');
        $reference = request()->validate(['reference' => ['required', 'string', 'max:120']])['reference'];
        $subscriptions->activate($subscription, $reference);

        return back()->with('status', 'Subscription activated for '.($subscription->seller->name ?? 'seller').'.');
    }

    private function validated(): array
    {
        $data = request()->validate([
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'billing_period' => ['required', Rule::in(['weekly', 'monthly', 'yearly', 'lifetime'])],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'listing_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'features' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);
        // Features are entered one per line and stored as a list.
        $data['features'] = filled($data['features'] ?? null)
         ? array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $data['features']))))
         : null;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }
}
