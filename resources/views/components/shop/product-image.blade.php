@props(['product', 'path' => null, 'eager' => false])
@php
    $path ??= $product->coverImage();
@endphp
<div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-paper-raised']) }}>
    @if ($path)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}"
             alt="{{ $product->name }}"
             @if (! $eager) loading="lazy" @endif
             decoding="async"
             class="h-full w-full object-cover">
    @else
        <div class="flex h-full w-full items-center justify-center p-[12%]">
            <x-shop.bottle :family="$product->scent_family?->value" :seed="$product->id" />
        </div>
    @endif
</div>
