@props(['html' => ''])
<div {{ $attributes->class('rich-content') }}>
    {!! \App\Support\RichText::toHtml($html) !!}
</div>
