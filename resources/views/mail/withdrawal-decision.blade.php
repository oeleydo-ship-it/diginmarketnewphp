<x-mail::message>
@if($withdrawal->status==='paid')
# Your withdrawal has been paid

Withdrawal **{{ $withdrawal->number }}** for **${{ number_format($withdrawal->net_amount,2) }}** (after a ${{ number_format($withdrawal->fee,2) }} fee) was transferred to your connected Stripe account.
@else
# Your withdrawal was declined

Withdrawal **{{ $withdrawal->number }}** was reviewed and declined. The reserved amount of **${{ number_format($withdrawal->amount,2) }}** has been returned to your available balance.
@endif

@if($withdrawal->administrator_note)
<x-mail::panel>
{{ $withdrawal->administrator_note }}
</x-mail::panel>
@endif

<x-mail::button :url="route('seller.finance')">
View your finance dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
