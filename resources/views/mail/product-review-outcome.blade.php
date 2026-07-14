<x-mail::message>
@if($outcome==='approved')
# Your product is live

**{{ $product->title }}** passed review and is now published on the marketplace.

<x-mail::button :url="route('products.show',$product->slug)">
View your product
</x-mail::button>
@else
# Changes requested

Our review team looked at **{{ $product->title }}** and needs a few changes before it can be published.

@if($notes)
<x-mail::panel>
{{ $notes }}
</x-mail::panel>
@endif

<x-mail::button :url="route('seller.products.index')">
Update your product
</x-mail::button>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
