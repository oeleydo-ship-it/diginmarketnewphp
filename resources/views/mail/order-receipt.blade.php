<x-mail::message>
# Thanks for your purchase

Order **{{ $order->number }}** is confirmed and your downloads are ready.

<x-mail::table>
| Product | License | Total |
|:--------|:--------|------:|
@foreach($order->items as $item)
| {{ $item->product_title }} | {{ $item->license?->license_key ?? $item->license_name }} | ${{ number_format($item->total,2) }} |
@endforeach
</x-mail::table>

**Order total: ${{ number_format($order->total,2) }} {{ $order->currency }}**

<x-mail::button :url="route('purchases.show',$order)">
View purchases & downloads
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
