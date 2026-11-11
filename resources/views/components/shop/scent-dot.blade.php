@props(['family'])
@php
    $value = $family instanceof \App\Enums\ScentFamily ? $family->value : $family;
    $class = [
        'woody' => 'bg-scent-woody',
        'floral' => 'bg-scent-floral',
        'fresh' => 'bg-scent-fresh',
        'oriental' => 'bg-scent-oriental',
        'gourmand' => 'bg-scent-gourmand',
    ][$value] ?? 'bg-ink-3';
@endphp
<span {{ $attributes->merge(['class' => "inline-block size-2 shrink-0 rounded-full {$class}"]) }} aria-hidden="true"></span>
