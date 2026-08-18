<x-admin-layout :title="'Review: '.$product->title">
<div class="mx-auto max-w-[960px] px-5 py-8 md:px-8 lg:py-10">
 <a href="{{ route('admin.products.review') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#3525cd] hover:underline"><span class="material-symbols-outlined text-[18px]">arrow_back</span>Back to review queue</a>
 <div class="mt-5 rounded-xl border border-[#d7d9e5] bg-white p-6 shadow-sm">
  <div class="flex flex-wrap items-center gap-2"><h1 class="text-2xl font-extrabold tracking-tight">{{ $product->title }}</h1><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold capitalize text-amber-800">{{ str($product->status->value ?? $product->status)->replace('_',' ') }}</span></div>
  <p class="mt-2 text-sm text-[#626576]">{{ $product->seller->name }} · {{ $product->category?->name }} · ${{ number_format((float) $product->regular_price, 2) }}</p>
  @if($product->short_description)<p class="mt-4 text-sm leading-6 text-[#424555]">{{ $product->short_description }}</p>@endif
  <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-semibold">
   @if($product->demo_url)<a href="{{ $product->demo_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[#3525cd] hover:underline">Live preview</a>@endif
   @if($product->status->value === 'published')<a href="{{ route('products.show', $product->slug) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[#3525cd] hover:underline">Public listing</a>@else<span class="text-xs font-normal text-[#777a8a]">Public listing appears after approve.</span>@endif
   @if($product->reviewVersion()?->downloadableFile())<a href="{{ route('admin.products.archive', $product) }}" class="inline-flex items-center gap-1 text-[#3525cd] hover:underline">Download zip</a>@endif
  </div>
  @if($product->images->isNotEmpty())
   <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
    @foreach($product->images as $image)
     <a href="{{ $image->url() }}" target="_blank" rel="noopener" class="overflow-hidden rounded-lg border border-[#d7d9e5] bg-[#f6f7fb]"><img src="{{ $image->url() }}" alt="{{ $image->original_name ?: $product->title }}" class="aspect-video w-full object-cover"></a>
    @endforeach
   </div>
  @endif
  @if($product->description)
   <div class="mt-6 border-t border-[#e2e4ec] pt-6">
    <h2 class="text-sm font-bold uppercase tracking-wider text-[#626576]">Description</h2>
    <x-rich-content class="mt-3 text-sm leading-7 text-[#424555]" :html="$product->description" />
   </div>
  @endif
  @if($product->versions->isNotEmpty())
   <div class="mt-6 border-t border-[#e2e4ec] pt-6">
    <h2 class="text-sm font-bold uppercase tracking-wider text-[#626576]">Versions</h2>
    <ul class="mt-3 space-y-2 text-sm text-[#424555]">
     @foreach($product->versions->sortByDesc('id') as $version)
      <li class="flex flex-wrap items-center gap-2"><span class="font-mono font-semibold">v{{ $version->version_number }}</span><span class="text-[#777a8a]">{{ $version->release_title }}</span><span class="rounded-full bg-[#f6f7fb] px-2 py-0.5 text-xs font-bold capitalize">{{ str($version->status->value)->replace('_',' ') }}</span></li>
     @endforeach
    </ul>
   </div>
  @endif
 </div>
</div>
</x-admin-layout>
