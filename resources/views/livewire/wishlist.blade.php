<div class="page pt-10">
    @include('partials.account-nav')

    <h1 class="mt-8 font-serif text-4xl">Wishlist</h1>

    @if (session('status'))
        <p class="mt-4 flex items-center gap-2 text-sm text-ink-2" role="status">
            <x-lucide-check class="size-4 text-green-700" stroke-width="1.5" />
            {{ session('status') }}
            <a href="{{ route('cart') }}" wire:navigate class="text-ink underline decoration-ink/30 underline-offset-4">Lihat keranjang</a>
        </p>
    @endif

    @if ($items->isEmpty())
        <div class="card mt-8 max-w-xl p-8">
            <p class="text-lg">Wishlist masih kosong</p>
            <p class="mt-1 text-sm text-ink-2">Tekan ikon hati di halaman produk untuk menyimpan parfum incaranmu.</p>
        </div>
    @else
        <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-10 sm:grid-cols-3 sm:gap-x-6 lg:grid-cols-4">
            @foreach ($items as $wish)
                <div wire:key="w-{{ $wish->id }}">
                    <x-shop.product-card :product="$wish->product" />
                    <div class="mt-3 flex items-center gap-2">
                        @if ($wish->product->isSoldOut())
                            <button type="button" disabled class="btn-secondary btn-sm flex-1">Habis</button>
                        @else
                            <button type="button" wire:click="moveToCart({{ $wish->id }})" class="btn-primary btn-sm flex-1">Pindah ke keranjang</button>
                        @endif
                        <button type="button" wire:click="remove({{ $wish->id }})" class="inline-flex size-9 items-center justify-center rounded-lg text-ink-3 hover:bg-ink/5 hover:text-ink" aria-label="Hapus dari wishlist">
                            <x-lucide-trash-2 class="size-4" stroke-width="1.5" />
                        </button>
                    </div>
                    @error('wish.'.$wish->id)
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>
    @endif
</div>
