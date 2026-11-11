@props(['product'])
@php($soldOut = $product->isSoldOut())
<a href="{{ route('products.show', $product) }}" wire:navigate {{ $attributes->merge(['class' => 'group block']) }}>
    <x-shop.product-image :product="$product" class="aspect-[4/5] rounded-xl">
    </x-shop.product-image>
    <div class="mt-3 space-y-1">
        <p class="meta">{{ $product->brand->name }}</p>
        <h3 class="text-[15px] font-medium leading-snug text-ink transition-colors duration-150 group-hover:text-ember">{{ $product->name }}</h3>
        <div class="flex items-center gap-2 pt-1 text-sm">
            @if ($soldOut)
                <span class="rounded-md bg-ink/5 px-2 py-0.5 text-[13px] font-medium text-ink-2">Habis</span>
            @else
                <span class="price text-ink-2">mulai <span class="text-ink">{{ rupiah($product->startingPrice()) }}</span></span>
            @endif
            <span class="ms-auto inline-flex items-center gap-1.5 text-[13px] text-ink-3">
                <x-shop.scent-dot :family="$product->scent_family" />
                {{ $product->scent_family->label() }}
            </span>
        </div>
    </div>
</a>
