<x-marketplace-layout title="Product review">
<div class="mx-auto max-w-7xl px-6 py-12">
 <h1 class="text-4xl font-black">Product review queue</h1>
 <div class="mt-6">@include('admin.partials.nav')</div>
 @if($errors->any())<p class="mt-4 rounded-xl border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-rose-300">{{ $errors->first() }}</p>@endif
 <div class="mt-8 space-y-4">
 @forelse($products as $product)
  <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
   <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
     <h2 class="text-xl font-bold">{{ $product->title }}</h2>
     <p class="text-slate-400">{{ $product->seller->name }} · {{ $product->category->name }} · submitted {{ $product->submitted_at?->diffForHumans() }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
     <form method="POST" action="{{ route('admin.products.approve',$product) }}">@csrf<input name="notes" placeholder="Review notes" class="rounded-lg bg-white/5 p-2"><button class="ml-2 rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-950">Approve & publish</button></form>
     <form method="POST" action="{{ route('admin.products.request-changes',$product) }}">@csrf<input name="notes" required placeholder="Required changes" class="rounded-lg bg-white/5 p-2"><button class="ml-2 rounded-lg border border-amber-400 px-4 py-2 font-bold text-amber-300">Request changes</button></form>
    </div>
   </div>
  </div>
 @empty<p class="text-slate-400">Review queue is clear.</p>@endforelse
 </div>
 <div class="mt-6">{{ $products->links() }}</div>
</div>
</x-marketplace-layout>
