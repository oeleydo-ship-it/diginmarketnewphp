@props(['product'])
@if($product->cover_image_path)
    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->cover_image_path) }}" alt="{{ $product->title }}"
        {{ $attributes->merge(['class' => 'object-cover']) }} loading="lazy">
@else
@php
    // Fall back to a deterministic branded placeholder for products without artwork.
    $gradients = [
        'bg-gradient-to-br from-[#4f46e5] to-[#8b7cf6]',
        'bg-gradient-to-br from-[#006c49] to-[#4edea3]',
        'bg-gradient-to-br from-[#885500] to-[#ffb95f]',
        'bg-gradient-to-br from-[#1e2f7a] to-[#4d44e3]',
        'bg-gradient-to-br from-[#5b21b6] to-[#c084fc]',
        'bg-gradient-to-br from-[#0e7490] to-[#67e8f9]',
    ];
    $icons = [
        'php-scripts' => 'code',
        'laravel-applications' => 'terminal',
        'wordpress-themes' => 'view_quilt',
        'javascript-applications' => 'javascript',
        'mobile-applications' => 'smartphone',
        'ui-templates' => 'palette',
    ];
    $icon = $product->category->icon ?: ($icons[$product->category->slug] ?? 'deployed_code');
@endphp
<div {{ $attributes->merge(['class' => 'relative flex items-center justify-center overflow-hidden '.$gradients[$product->id % count($gradients)]]) }}>
    <div class="absolute inset-0 opacity-15" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 22px 22px;"></div>
    <span class="material-symbols-outlined relative text-6xl text-white/90">{{ $icon }}</span>
    <span class="absolute bottom-3 left-4 font-mono text-[11px] uppercase tracking-widest text-white/80">{{ $product->category->name }}</span>
</div>
@endif
