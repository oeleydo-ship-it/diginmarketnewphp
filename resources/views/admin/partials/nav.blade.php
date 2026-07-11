<div class="flex flex-wrap gap-3 text-sm">
 <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-white/10 px-3 py-1.5 {{ request()->routeIs('admin.dashboard')?'bg-emerald-400 font-semibold text-slate-950':'text-slate-300' }}">Overview</a>
 <a href="{{ route('admin.products.review') }}" class="rounded-lg border border-white/10 px-3 py-1.5 {{ request()->routeIs('admin.products.*')?'bg-emerald-400 font-semibold text-slate-950':'text-slate-300' }}">Products</a>
 <a href="{{ route('admin.sellers.index') }}" class="rounded-lg border border-white/10 px-3 py-1.5 {{ request()->routeIs('admin.sellers.*')?'bg-emerald-400 font-semibold text-slate-950':'text-slate-300' }}">Sellers</a>
 <a href="{{ route('admin.orders.index') }}" class="rounded-lg border border-white/10 px-3 py-1.5 {{ request()->routeIs('admin.orders.*')?'bg-emerald-400 font-semibold text-slate-950':'text-slate-300' }}">Orders</a>
 <a href="{{ route('admin.refunds.index') }}" class="rounded-lg border border-white/10 px-3 py-1.5 {{ request()->routeIs('admin.refunds.*')?'bg-emerald-400 font-semibold text-slate-950':'text-slate-300' }}">Refunds</a>
 <a href="{{ route('admin.withdrawals.index') }}" class="rounded-lg border border-white/10 px-3 py-1.5 {{ request()->routeIs('admin.withdrawals.*')?'bg-emerald-400 font-semibold text-slate-950':'text-slate-300' }}">Withdrawals</a>
 <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-white/10 px-3 py-1.5 {{ request()->routeIs('admin.users.*')?'bg-emerald-400 font-semibold text-slate-950':'text-slate-300' }}">Users</a>
 <a href="{{ route('admin.pages.index') }}" class="rounded-lg border border-white/10 px-3 py-1.5 {{ request()->routeIs('admin.pages.*')?'bg-emerald-400 font-semibold text-slate-950':'text-slate-300' }}">Pages</a>
 <a href="{{ route('admin.settings.index') }}" class="rounded-lg border border-white/10 px-3 py-1.5 {{ request()->routeIs('admin.settings.*')?'bg-emerald-400 font-semibold text-slate-950':'text-slate-300' }}">Settings</a>
 <a href="{{ route('admin.audits.index') }}" class="rounded-lg border border-white/10 px-3 py-1.5 {{ request()->routeIs('admin.audits.*')?'bg-emerald-400 font-semibold text-slate-950':'text-slate-300' }}">Audit log</a>
</div>
@if(session('status'))<p class="mt-4 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-emerald-300">{{ session('status') }}</p>@endif
