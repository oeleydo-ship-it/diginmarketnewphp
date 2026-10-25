<x-mail::message>
# {{ $heading }}

{{ $body }}

@if($notes)
<x-mail::panel>
{{ $notes }}
</x-mail::panel>
@endif

<x-mail::button :url="$actionUrl">
{{ $actionLabel }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
