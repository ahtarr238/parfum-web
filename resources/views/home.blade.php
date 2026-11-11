<x-app-layout>
    {{-- Hero: teks di kiri, meja botol di kanan --}}
    <section class="page pt-10 sm:pt-16">
        <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
            <div class="max-w-xl">
                <h1 class="font-serif text-[44px] leading-[1.04] tracking-[-0.02em] sm:text-6xl lg:text-[68px]">Cari parfum dari aromanya, bukan dari tebakan</h1>
                <p class="mt-6 max-w-md text-lg leading-8 text-ink-2">Pilih keluarga aroma, lihat top, middle, dan base notes-nya, lalu coba lewat decant sebelum membeli botol penuh.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('catalog') }}" wire:navigate class="btn-primary">Lihat katalog</a>
                    <a href="{{ route('catalog', ['ukuran' => ['decant']]) }}" wire:navigate class="btn-secondary">Coba decant</a>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-3xl bg-paper-raised">
                <div class="grid aspect-[5/4] grid-cols-3 items-end gap-2 px-6 pb-8 pt-12 sm:px-12">
                    <x-shop.bottle family="fresh" :seed="2" class="h-[78%] w-full" />
                    <x-shop.bottle family="oriental" :seed="0" class="h-full w-full" />
                    <x-shop.bottle family="floral" :seed="1" class="h-[70%] w-full" />
                </div>
                <div class="absolute inset-x-0 bottom-0 h-8 border-t border-line bg-paper-card/60"></div>
            </div>
        </div>
    </section>

    {{-- Keluarga aroma (signature) --}}
    <section class="page mt-20">
        <h2 class="text-2xl font-medium">Mulai dari keluarga aroma</h2>
        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @php
                $scentCopy = [
                    'woody' => 'Kayu, akar wangi, cedar',
                    'floral' => 'Melati, mawar, kenanga',
                    'fresh' => 'Citrus, teh, udara laut',
                    'oriental' => 'Rempah, amber, oud',
                    'gourmand' => 'Vanila, kopi, karamel',
                ];
            @endphp
            @foreach ($scents as $scent)
                <a href="{{ route('catalog', ['aroma' => [$scent->value]]) }}" wire:navigate
                   class="card group flex flex-col gap-3 p-5 transition-colors duration-150 hover:border-ink/25">
                    <span class="flex items-center gap-2 text-[15px] font-medium">
                        <x-shop.scent-dot :family="$scent" class="size-2.5" />
                        {{ $scent->label() }}
                    </span>
                    <span class="text-sm text-ink-2">{{ $scentCopy[$scent->value] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Kategori --}}
    <section class="page mt-20">
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ($categories as $category)
                <a href="{{ route('categories.show', $category) }}" wire:navigate
                   class="group flex items-center justify-between rounded-2xl border border-line px-6 py-6 transition-colors duration-150 hover:bg-paper-card">
                    <span>
                        <span class="block font-serif text-3xl">{{ $category->name }}</span>
                        <span class="meta tabular-nums">{{ $category->products_count }} parfum</span>
                    </span>
                    <x-lucide-arrow-right class="size-5 text-ink-3 transition-transform duration-150 group-hover:translate-x-0.5" stroke-width="1.5" />
                </a>
            @endforeach
        </div>
    </section>

    {{-- Terbaru --}}
    <section class="page mt-20">
        <div class="flex items-end justify-between gap-4">
            <h2 class="text-2xl font-medium">Baru masuk</h2>
            <a href="{{ route('catalog') }}" wire:navigate class="text-sm text-ink-2 hover:text-ink">Semua parfum</a>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-10 sm:grid-cols-4 sm:gap-x-6">
            @foreach ($newest as $product)
                <x-shop.product-card :product="$product" />
            @endforeach
        </div>
    </section>

    @if ($bestsellers->contains(fn ($p) => (int) $p->sold_qty > 0))
        <section class="page mt-20">
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-2xl font-medium">Paling sering dibeli</h2>
                <a href="{{ route('catalog', ['urut' => 'terlaris']) }}" wire:navigate class="text-sm text-ink-2 hover:text-ink">Lihat terlaris</a>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-10 sm:grid-cols-4 sm:gap-x-6">
                @foreach ($bestsellers as $product)
                    <x-shop.product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
