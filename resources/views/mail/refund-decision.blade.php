<x-mail::message>
@if($refund->status==='refunded')
# Your refund was approved

Refund request **{{ $refund->number }}** was approved for **${{ number_format($refund->approved_amount,2) }}**. The amount will be returned to your original payment method by Stripe; depending on your bank it can take 5–10 business days to appear.
@else
# Your refund request was declined

Refund request **{{ $refund->number }}** was reviewed and declined.
@endif

@if($refund->administrator_decision)
<x-mail::panel>
{{ $refund->administrator_decision }}
</x-mail::panel>
@endif

<x-mail::button :url="route('purchases.index')">
View your purchases
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
