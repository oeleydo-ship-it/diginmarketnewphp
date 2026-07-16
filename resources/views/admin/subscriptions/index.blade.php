<x-admin-layout title="Subscription Plans">
<div class="mx-auto max-w-[1400px] px-5 py-8 md:px-8 lg:py-10">
    <div><h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">Subscription plans</h1><p class="mt-2 text-[#626576]">Seller membership tiers. Commission override and listing limits apply while a plan is active.</p></div>

    @if(session('status'))<div class="mt-6 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mt-6 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

    @if($pending->isNotEmpty())
        <section class="mt-8 rounded-2xl border border-[#d7d9e5] bg-white p-6">
            <h2 class="text-lg font-bold">Pending activation ({{ $pending->count() }})</h2>
            <p class="text-sm text-[#626576]">Confirm the seller's payment to switch their plan on.</p>
            <div class="mt-4 space-y-3">
                @foreach($pending as $sub)
                    <form method="POST" action="{{ route('admin.subscriptions.activate', $sub) }}" class="flex flex-wrap items-center gap-3 rounded-xl border border-[#e5e7f0] p-3">@csrf
                        <div class="flex-1">
                            <p class="font-semibold">{{ $sub->seller->name ?? 'Seller #'.$sub->user_id }} — {{ $sub->plan->name }}</p>
                            <p class="text-xs text-[#626576]">${{ number_format((float) $sub->price, 2) }} · {{ $sub->billing_period }} · requested {{ $sub->created_at->diffForHumans() }}</p>
                        </div>
                        <input name="reference" required placeholder="Payment reference" class="rounded-lg border border-[#d7d9e5] px-3 py-2 text-sm">
                        <button class="rounded-lg bg-[#3525cd] px-4 py-2 text-sm font-semibold text-white">Activate</button>
                    </form>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-8 grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
        @foreach($plans as $plan)
            <div class="rounded-2xl border border-[#d7d9e5] bg-white p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-bold">{{ $plan->name }} @unless($plan->is_active)<span class="ml-1 rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">Inactive</span>@endunless</h3>
                        <p class="text-sm text-[#626576]">${{ number_format((float) $plan->price, 2) }} · {{ $plan->billing_period }} · {{ $plan->subscriptions_count }} active</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.subscription-plans.update', $plan) }}" class="mt-4 space-y-3 text-sm">@csrf @method('PUT')
                    <div class="grid grid-cols-2 gap-3">
                        <label class="block">Name<input name="name" value="{{ $plan->name }}" class="mt-1 w-full rounded-lg border border-[#d7d9e5] px-3 py-2"></label>
                        <label class="block">Price<input name="price" type="number" step="0.01" min="0" value="{{ (float) $plan->price }}" class="mt-1 w-full rounded-lg border border-[#d7d9e5] px-3 py-2"></label>
                        <label class="block">Billing period<select name="billing_period" class="mt-1 w-full rounded-lg border border-[#d7d9e5] px-3 py-2">@foreach(['weekly','monthly','yearly','lifetime'] as $p)<option value="{{ $p }}" @selected($plan->billing_period === $p)>{{ ucfirst($p) }}</option>@endforeach</select></label>
                        <label class="block">Commission % (blank = default)<input name="commission_rate" type="number" step="0.01" min="0" max="100" value="{{ $plan->commission_rate }}" class="mt-1 w-full rounded-lg border border-[#d7d9e5] px-3 py-2"></label>
                        <label class="block">Listing limit (blank = unlimited)<input name="listing_limit" type="number" min="1" value="{{ $plan->listing_limit }}" class="mt-1 w-full rounded-lg border border-[#d7d9e5] px-3 py-2"></label>
                        <label class="block">Sort order<input name="sort_order" type="number" min="0" value="{{ $plan->sort_order }}" class="mt-1 w-full rounded-lg border border-[#d7d9e5] px-3 py-2"></label>
                    </div>
                    <label class="block">Features (one per line)<textarea name="features" rows="3" class="mt-1 w-full rounded-lg border border-[#d7d9e5] px-3 py-2">{{ implode("\n", $plan->features ?? []) }}</textarea></label>
                    <label class="flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($plan->is_active)> Active</label>
                    <button class="rounded-lg bg-[#3525cd] px-4 py-2 font-semibold text-white">Save</button>
                </form>
                <form method="POST" action="{{ route('admin.subscription-plans.destroy', $plan) }}" class="mt-3" onsubmit="return confirm('Deactivate this plan?')">@csrf @method('DELETE')<button class="text-sm font-semibold text-red-600 hover:underline">Deactivate</button></form>
            </div>
        @endforeach
    </section>

    <section class="mt-8 rounded-2xl border border-[#d7d9e5] bg-white p-6">
        <h2 class="text-lg font-bold">Create a plan</h2>
        <form method="POST" action="{{ route('admin.subscription-plans.store') }}" class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">@csrf
            <label class="block">Name<input name="name" required class="mt-1 w-full rounded-lg border border-[#d7d9e5] px-3 py-2"></label>
            <label class="block">Price<input name="price" type="number" step="0.01" min="0" value="0" required class="mt-1 w-full rounded-lg border border-[#d7d9e5] px-3 py-2"></label>
            <label class="block">Billing period<select name="billing_period" class="mt-1 w-full rounded-lg border border-[#d7d9e5] px-3 py-2">@foreach(['weekly','monthly','yearly','lifetime'] as $p)<option value="{{ $p }}">{{ ucfirst($p) }}</option>@endforeach</select></label>
            <label class="block">Commission % (blank = default)<input name="commission_rate" type="number" step="0.01" min="0" max="100" class="mt-1 w-full rounded-lg border border-[#d7d9e5] px-3 py-2"></label>
            <label class="block">Listing limit (blank = unlimited)<input name="listing_limit" type="number" min="1" class="mt-1 w-full rounded-lg border border-[#d7d9e5] px-3 py-2"></label>
            <label class="block">Sort order<input name="sort_order" type="number" min="0" value="0" class="mt-1 w-full rounded-lg border border-[#d7d9e5] px-3 py-2"></label>
            <label class="block sm:col-span-2 lg:col-span-3">Features (one per line)<textarea name="features" rows="3" class="mt-1 w-full rounded-lg border border-[#d7d9e5] px-3 py-2"></textarea></label>
            <label class="flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked> Active</label>
            <div class="sm:col-span-2 lg:col-span-3"><button class="rounded-lg bg-[#3525cd] px-5 py-2.5 font-semibold text-white">Create plan</button></div>
        </form>
    </section>
</div>
</x-admin-layout>
