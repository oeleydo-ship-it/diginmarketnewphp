@props(['rating' => 0, 'size' => 18])
@php $rating = (float) $rating; @endphp
<div {{ $attributes->merge(['class' => 'flex items-center text-tertiary-fixed-dim']) }} aria-label="Rated {{ number_format($rating, 1) }} out of 5">
    @for($i = 1; $i <= 5; $i++)
        @if($rating >= $i - 0.25)
            <span class="material-symbols-outlined icon-fill" style="font-size:{{ $size }}px">star</span>
        @elseif($rating >= $i - 0.75)
            <span class="material-symbols-outlined icon-fill" style="font-size:{{ $size }}px">star_half</span>
        @else
            <span class="material-symbols-outlined" style="font-size:{{ $size }}px">star</span>
        @endif
    @endfor
</div>
