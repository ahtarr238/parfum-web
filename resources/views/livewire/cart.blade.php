<div class="page pt-10">
    <h1 class="font-serif text-4xl sm:text-5xl">Keranjang</h1>

    @if ($this->summary->isEmpty())
        <div class="card mt-8 flex max-w-xl flex-col items-start gap-4 p-8">
            <p class="text-lg">Keranjangmu masih kosong</p>
            <p class="text-sm text-ink-2">Pilih parfum dari katalog, lalu tambahkan ukuran yang kamu mau.</p>
            <a href="{{ route('catalog') }}" wire:navigate class="btn-primary btn-sm">Lihat katalog</a>
        </div>
    @else
        <div class="mt-8 grid gap-10 lg:grid-cols-[1fr_360px]">
            <ul class="divide-y divide-line border-y border-line">
                @foreach ($this->summary->items as $item)
                    @php($variant = $item->variant)
                    <li class="flex gap-4 py-5 sm:gap-6" wire:key="ci-{{ $item->id }}">
                        <a href="{{ route('products.show', $variant->product) }}" wire:navigate class="shrink-0">
                            <x-shop.product-image :product="$variant->product" class="aspect-[4/5] w-20 rounded-lg sm:w-24" />
                        </a>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1">
                                <div class="min-w-0">
                                    <p class="meta">{{ $variant->product->brand->name }}</p>
                                    <a href="{{ route('products.show', $variant->product) }}" wire:navigate class="text-[15px] font-medium hover:text-ember">{{ $variant->product->name }}</a>
                                    <p class="mt-0.5 text-sm text-ink-2">{{ $variant->label() }} · <span class="tabular-nums">{{ rupiah($variant->price) }}</span></p>
                                </div>
                                <p class="price text-[15px] font-medium">{{ rupiah($variant->price * $item->qty) }}</p>
                            </div>

                            <div class="mt-auto flex items-center gap-3 pt-3">
                                <div class="inline-flex h-9 items-center rounded-lg border border-line bg-paper-card">
                                    <button type="button" wire:click="setQty({{ $item->id }}, {{ $item->qty - 1 }})" class="inline-flex size-9 items-center justify-center text-ink-2 hover:text-ink" aria-label="Kurangi">
                                        <x-lucide-minus class="size-4" stroke-width="1.5" />
                                    </button>
                                    <span class="w-8 text-center text-sm tabular-nums">{{ $item->qty }}</span>
                                    <button type="button" wire:click="setQty({{ $item->id }}, {{ $item->qty + 1 }})" class="inline-flex size-9 items-center justify-center text-ink-2 hover:text-ink disabled:opacity-40" aria-label="Tambah" @disabled($item->qty >= $variant->stock)>
                                        <x-lucide-plus class="size-4" stroke-width="1.5" />
                                    </button>
                                </div>
                                <button type="button" wire:click="remove({{ $item->id }})" class="text-sm text-ink-3 hover:text-red-700">Hapus</button>
                            </div>

                            @if ($item->problem)
                                <p class="mt-2 text-sm text-red-700">{{ $item->problem }}</p>
                            @endif
                            @error('qty.'.$item->id)
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </li>
                @endforeach
            </ul>

            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="card p-6">
                    <h2 class="text-lg font-medium">Ringkasan</h2>
                    <dl class="mt-4 space-y-3 text-[15px]">
                        <div class="flex justify-between">
                            <dt class="text-ink-2">Subtotal ({{ $this->summary->itemCount }} barang)</dt>
                            <dd class="price">{{ rupiah($this->summary->subtotal) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-2">Total berat</dt>
                            <dd class="tabular-nums">{{ number_format($this->summary->totalWeight, 0, ',', '.') }} g</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-2">Ongkir</dt>
                            <dd class="text-ink-3">dihitung saat checkout</dd>
                        </div>
                    </dl>
                    @if ($canCheckout)
                        <a href="{{ route('checkout') }}" wire:navigate class="btn-primary mt-6 w-full">Checkout</a>
                    @else
                        <button type="button" disabled class="btn-primary mt-6 w-full">Checkout</button>
                        <p class="meta mt-2">Perbaiki item yang ditandai merah dulu.</p>
                    @endif
                </div>
            </aside>
        </div>
    @endif
</div>
