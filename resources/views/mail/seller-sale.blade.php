<x-mail::message>
# You made a sale 🎉

**{{ $item->product_title }}** ({{ $item->license_name }}) just sold for **${{ number_format($item->total,2) }}**.

Your earning after the platform commission is **${{ number_format($item->seller_earning,2) }}**. It will appear in your pending balance and clear to your available balance after the standard clearance window.

<x-mail::button :url="route('seller.finance')">
View earnings
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
