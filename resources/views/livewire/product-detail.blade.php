<div>
    <div class="page pt-6">
        <nav class="meta flex flex-wrap items-center gap-1.5" aria-label="Breadcrumb">
            <a href="{{ route('catalog') }}" wire:navigate class="hover:text-ink">Katalog</a>
            <x-lucide-chevron-right class="size-3.5" stroke-width="1.5" />
            <a href="{{ route('categories.show', $product->category) }}" wire:navigate class="hover:text-ink">{{ $product->category->name }}</a>
            <x-lucide-chevron-right class="size-3.5" stroke-width="1.5" />
            <span class="text-ink-2">{{ $product->name }}</span>
        </nav>
    </div>

    <div class="page mt-6 grid gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:gap-16">
        {{-- Galeri --}}
        @php($images = $product->images ?: [null])
        <div x-data="{ active: 0 }" class="lg:sticky lg:top-24 lg:self-start">
            @foreach ($images as $i => $path)
                <x-shop.product-image :product="$product" :path="$path" :eager="$i === 0"
                    x-show="active === {{ $i }}" class="aspect-[4/5] rounded-2xl" />
            @endforeach
            @if (count($images) > 1)
                <div class="mt-3 grid grid-cols-5 gap-2">
                    @foreach ($images as $i => $path)
                        <button type="button" @click="active = {{ $i }}" class="overflow-hidden rounded-lg border-2 transition-colors duration-150"
                                :class="active === {{ $i }} ? 'border-ink' : 'border-transparent'" aria-label="Foto {{ $i + 1 }}">
                            <x-shop.product-image :product="$product" :path="$path" class="aspect-square" />
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Info --}}
        <div>
            <a href="{{ route('brands.show', $product->brand) }}" wire:navigate class="text-sm text-ink-2 hover:text-ink">{{ $product->brand->name }}</a>
            <h1 class="mt-2 font-serif text-4xl leading-tight sm:text-5xl">{{ $product->name }}</h1>

            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-2">
                <span class="inline-flex items-center gap-1.5">
                    <x-shop.scent-dot :family="$product->scent_family" />
                    {{ $product->scent_family->label() }}
                </span>
                <span>{{ $product->category->name }}</span>
                @if ($rating)
                    <a href="#ulasan" class="inline-flex items-center gap-1 tabular-nums hover:text-ink">
                        <x-lucide-star class="size-4 fill-ember text-ember" stroke-width="1.5" />
                        {{ number_format($rating, 1, ',', '') }}
                        <span class="text-ink-3">({{ $reviewCount }} ulasan)</span>
                    </a>
                @endif
            </div>

            <div class="mt-8 border-t border-line pt-8">
                @if ($this->variant)
                    <p class="price text-3xl font-medium" data-testid="variant-price">{{ rupiah($this->variant->price) }}</p>
                    <p class="meta mt-1 tabular-nums" data-testid="variant-meta">
                        @if ($soldOut || $this->variant->stock === 0)
                            Stok habis
                        @else
                            Stok {{ $this->variant->stock }}
                        @endif
                        · Berat {{ number_format($this->variant->weight_gram, 0, ',', '.') }} g
                    </p>
                @endif

                <fieldset class="mt-6">
                    <legend class="label">Ukuran</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($product->variants as $variant)
                            <button type="button" wire:click="selectVariant({{ $variant->id }})" wire:key="v-{{ $variant->id }}"
                                @class([
                                    'relative inline-flex h-11 min-w-[72px] items-center justify-center rounded-lg border px-4 text-[15px] tabular-nums transition-colors duration-150',
                                    'border-ink bg-ink text-paper-card' => $variant->id === $variantId,
                                    'border-line bg-paper-card text-ink hover:border-ink/40' => $variant->id !== $variantId && $variant->stock > 0,
                                    'border-line bg-paper-card text-ink-3 line-through decoration-ink/30' => $variant->id !== $variantId && $variant->stock === 0,
                                ])
                                aria-pressed="{{ $variant->id === $variantId ? 'true' : 'false' }}">
                                {{ $variant->label() }}
                            </button>
                        @endforeach
                    </div>
                </fieldset>

                @if ($soldOut || ! $this->variant || $this->variant->stock === 0)
                    <div class="mt-8 flex gap-3">
                        <button type="button" disabled class="btn-primary flex-1">Habis</button>
                        <button type="button" wire:click="toggleWishlist" class="btn-secondary w-11 px-0" aria-label="{{ $this->inWishlist ? 'Hapus dari wishlist' : 'Simpan ke wishlist' }}">
                            <x-lucide-heart @class(['size-[18px]', 'fill-ember text-ember' => $this->inWishlist]) stroke-width="1.5" />
                        </button>
                    </div>
                    <p class="meta mt-3">Simpan ke wishlist untuk membelinya nanti.</p>
                @else
                    <div class="mt-8 flex flex-wrap gap-3">
                        <div class="inline-flex h-11 items-center rounded-lg border border-line bg-paper-card">
                            <button type="button" wire:click="decrement" class="inline-flex size-11 items-center justify-center text-ink-2 hover:text-ink" aria-label="Kurangi jumlah">
                                <x-lucide-minus class="size-4" stroke-width="1.5" />
                            </button>
                            <span class="w-8 text-center tabular-nums" data-testid="qty">{{ $qty }}</span>
                            <button type="button" wire:click="increment" class="inline-flex size-11 items-center justify-center text-ink-2 hover:text-ink" aria-label="Tambah jumlah">
                                <x-lucide-plus class="size-4" stroke-width="1.5" />
                            </button>
                        </div>
                        <button type="button" wire:click="addToCart" wire:loading.attr="disabled" class="btn-primary flex-1">
                            Tambah ke keranjang
                        </button>
                        <button type="button" wire:click="toggleWishlist" class="btn-secondary w-11 px-0" aria-label="{{ $this->inWishlist ? 'Hapus dari wishlist' : 'Simpan ke wishlist' }}">
                            <x-lucide-heart @class(['size-[18px]', 'fill-ember text-ember' => $this->inWishlist]) stroke-width="1.5" />
                        </button>
                    </div>
                    @error('qty')
                        <p class="mt-3 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    @if (session('cart-added'))
                        <p class="mt-3 flex items-center gap-2 text-sm text-ink-2" role="status">
                            <x-lucide-check class="size-4 text-green-700" stroke-width="1.5" />
                            {{ $product->name }} {{ session('cart-added') }} masuk keranjang.
                            <a href="{{ route('cart') }}" wire:navigate class="text-ink underline decoration-ink/30 underline-offset-4">Lihat keranjang</a>
                        </p>
                    @endif
                @endif
            </div>

            {{-- Notes --}}
            <div class="mt-10 border-t border-line pt-8">
                <h2 class="text-lg font-medium">Notes</h2>
                <dl class="mt-5 space-y-4">
                    @foreach (['top' => ['Top notes', 'Tercium pertama, 15–30 menit'], 'middle' => ['Middle notes', 'Inti aroma, beberapa jam'], 'base' => ['Base notes', 'Bertahan paling lama']] as $layer => [$label, $hint])
                        @if ($product->notesList($layer))
                            <div class="grid grid-cols-[112px_1fr] gap-4">
                                <dt>
                                    <span class="block text-sm text-ink">{{ $label }}</span>
                                    <span class="meta">{{ $hint }}</span>
                                </dt>
                                <dd class="flex flex-wrap gap-2">
                                    @foreach ($product->notesList($layer) as $note)
                                        <span class="inline-flex h-8 items-center rounded-md border border-line bg-paper-card px-3 text-sm text-ink-2">{{ $note }}</span>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </div>

            <div class="mt-10 border-t border-line pt-8">
                <h2 class="text-lg font-medium">Deskripsi</h2>
                <div class="mt-3 max-w-prose text-[15px] leading-7 text-ink-2">{!! nl2br(e($product->description)) !!}</div>
            </div>
        </div>
    </div>

    {{-- Ulasan --}}
    <section id="ulasan" class="page mt-20 scroll-mt-24">
        <div class="flex items-end justify-between gap-4 border-b border-line pb-4">
            <h2 class="text-2xl font-medium">Ulasan pembeli</h2>
            @if ($rating)
                <p class="tabular-nums text-ink-2"><span class="text-ink">{{ number_format($rating, 1, ',', '') }}</span> dari 5 · {{ $reviewCount }} ulasan</p>
            @endif
        </div>
        @forelse ($reviews as $review)
            <article class="grid gap-2 border-b border-line py-6 sm:grid-cols-[200px_1fr] sm:gap-8" wire:key="r-{{ $review->id }}">
                <div>
                    <p class="text-sm text-ink">{{ $review->user->name }}</p>
                    <p class="meta">{{ $review->created_at->translatedFormat('j F Y') }}</p>
                </div>
                <div>
                    <div class="flex gap-0.5" aria-label="{{ $review->rating }} dari 5 bintang">
                        @for ($i = 1; $i <= 5; $i++)
                            <x-lucide-star @class(['size-4', 'fill-ember text-ember' => $i <= $review->rating, 'text-ink/20' => $i > $review->rating]) stroke-width="1.5" />
                        @endfor
                    </div>
                    @if ($review->comment)
                        <p class="mt-2 max-w-prose text-[15px] leading-7 text-ink-2">{{ $review->comment }}</p>
                    @endif
                </div>
            </article>
        @empty
            <p class="py-8 text-ink-2">Belum ada ulasan. Ulasan bisa ditulis pembeli setelah pesanannya selesai.</p>
        @endforelse
    </section>

    @if ($related->isNotEmpty())
        <section class="page mt-20">
            <h2 class="text-2xl font-medium">Aroma serupa</h2>
            <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-10 sm:grid-cols-4 sm:gap-x-6">
                @foreach ($related as $item)
                    <x-shop.product-card :product="$item" wire:key="rel-{{ $item->id }}" />
                @endforeach
            </div>
        </section>
    @endif
</div>
