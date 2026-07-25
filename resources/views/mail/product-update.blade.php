<x-mail::message>
# A new version is ready

**{{ $product->title }} {{ $version->version_number }}**@if($version->release_title) — {{ $version->release_title }}@endif is available to download with your existing licence. Updates are free for the lifetime of your purchase.

@if($version->release_notes)
<x-mail::panel>
{{ $version->release_notes }}
</x-mail::panel>
@endif

<x-mail::button :url="$downloadUrl">
Download the update
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
