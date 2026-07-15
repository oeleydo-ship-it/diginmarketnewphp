<x-admin-layout title="Products">
<div class="mx-auto max-w-[1400px] px-5 py-8 md:px-8 lg:py-10">
 <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
  <div><h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">Products</h1><p class="mt-2 text-[#626576]">Every listing across the marketplace. Review the queue from <a href="{{ route('admin.products.review') }}" class="font-semibold text-[#3525cd] hover:underline">Product review</a>.</p></div>
  <a href="{{ route('admin.products.review') }}" class="flex h-fit items-center gap-2 rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#2a1da8]"><span class="material-symbols-outlined text-[18px]">rate_review</span>Review queue</a>
 </div>

 <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
  @php($cards = [['Total', $metrics['total'], 'inventory_2', 'text-[#3525cd]'], ['Published', $metrics['published'], 'check_circle', 'text-emerald-600'], ['In review', $metrics['pending'], 'hourglass_top', 'text-amber-600'], ['Draft / rejected', $metrics['draft'], 'edit_note', 'text-[#777a8a]']])
  @foreach($cards as [$label, $value, $icon, $accent])
   <div class="rounded-xl border border-[#d7d9e5] bg-white p-5 shadow-sm"><span class="material-symbols-outlined {{ $accent }}">{{ $icon }}</span><p class="mt-1 text-2xl font-bold">{{ number_format($value) }}</p><p class="text-sm text-[#626576]">{{ $label }}</p></div>
  @endforeach
 </div>

 <form method="GET" class="mt-8 flex flex-wrap gap-3">
  <input name="q" value="{{ request('q') }}" placeholder="Search title" class="min-w-[200px] flex-1 rounded-lg border border-[#d7d9e5] bg-[#fafbff] px-3 py-2.5 text-sm outline-none focus:border-[#3525cd]">
  <select name="status" class="rounded-lg border border-[#d7d9e5] bg-[#fafbff] px-3 py-2.5 text-sm outline-none focus:border-[#3525cd]"><option value="">All statuses</option>@foreach($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ str($s)->headline() }}</option>@endforeach</select>
  <select name="category" class="rounded-lg border border-[#d7d9e5] bg-[#fafbff] px-3 py-2.5 text-sm outline-none focus:border-[#3525cd]"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->name }}</option>@endforeach</select>
  <select name="sort" class="rounded-lg border border-[#d7d9e5] bg-[#fafbff] px-3 py-2.5 text-sm outline-none focus:border-[#3525cd]"><option value="">Newest</option><option value="sales" @selected(request('sort') === 'sales')>Best selling</option></select>
  <button class="rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#2a1da8]">Filter</button>
 </form>

 <div class="mt-5 overflow-hidden rounded-xl border border-[#d7d9e5] bg-white shadow-sm">
  <div class="overflow-x-auto"><table class="w-full min-w-[900px] text-left text-sm">
   <thead class="bg-[#edf2ff] text-xs uppercase tracking-[.08em] text-[#424555]"><tr><th class="px-5 py-4">Product</th><th class="px-5 py-4">Seller</th><th class="px-5 py-4">Category</th><th class="px-5 py-4 text-right">Price</th><th class="px-5 py-4 text-right">Sales</th><th class="px-5 py-4">Status</th></tr></thead>
   <tbody class="divide-y divide-[#e2e4ec]">
   @forelse($products as $product)
    @php($st = $product->status->value)
    <tr class="hover:bg-[#fafbff]">
     <td class="px-5 py-4"><div class="flex items-center gap-3"><span class="flex h-10 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-[#edf2ff]">@if($product->cover_image_path)<img src="{{ str_starts_with($product->cover_image_path,'http') ? $product->cover_image_path : \Illuminate\Support\Facades\Storage::disk('public')->url($product->cover_image_path) }}" class="h-full w-full object-cover" alt="">@else<span class="material-symbols-outlined text-[18px] text-[#3525cd]">{{ $product->category?->icon ?: 'deployed_code' }}</span>@endif</span><div class="min-w-0">@if($st === 'published')<a href="{{ route('products.show',$product->slug) }}" target="_blank" rel="noopener" class="truncate font-semibold text-[#251bd5] hover:underline">{{ $product->title }}</a>@else<span class="truncate font-semibold">{{ $product->title }}</span>@endif<p class="font-mono text-[11px] text-[#777a8a]">/{{ $product->slug }}</p></div></div></td>
     <td class="px-5 py-4 text-[#626576]">{{ $product->seller?->sellerProfile?->display_name ?? $product->seller?->name ?? '—' }}</td>
     <td class="px-5 py-4 text-[#626576]">{{ $product->category?->name ?? '—' }}</td>
     <td class="px-5 py-4 text-right font-semibold">${{ number_format((float) $product->regular_price, 2) }}</td>
     <td class="px-5 py-4 text-right">{{ number_format($product->sales_count) }}</td>
     <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold {{ in_array($st,['published','approved'],true) ? 'bg-emerald-50 text-emerald-700' : (in_array($st,['submitted','under_review'],true) ? 'bg-amber-50 text-amber-700' : (in_array($st,['rejected','suspended'],true) ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-600')) }}">{{ str($st)->headline() }}</span></td>
    </tr>
   @empty
    <tr><td colspan="6" class="px-6 py-16 text-center text-[#777a8a]"><span class="material-symbols-outlined mb-2 block text-4xl">inventory_2</span>No products match these filters.</td></tr>
   @endforelse
   </tbody>
  </table></div>
 </div>
 <div class="mt-6">{{ $products->links() }}</div>
</div>
</x-admin-layout>
