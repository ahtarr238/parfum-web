<div class="page pt-10">
    <h1 class="font-serif text-4xl sm:text-5xl">Checkout</h1>

    @error('order')
        <p class="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $message }}</p>
    @enderror

    <div class="mt-8 grid gap-10 lg:grid-cols-[1fr_380px]">
        <div class="space-y-10">
            {{-- 1. Alamat --}}
            <section>
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-medium">Alamat pengiriman</h2>
                    <a href="{{ route('addresses', ['kembali' => 'checkout']) }}" wire:navigate class="text-sm text-ink-2 hover:text-ink">Kelola alamat</a>
                </div>

                @if ($this->addresses->isEmpty())
                    <div class="card mt-4 p-6">
                        <p class="text-ink">Belum ada alamat</p>
                        <p class="mt-1 text-sm text-ink-2">Tambahkan alamat dulu supaya ongkir bisa dihitung.</p>
                        <a href="{{ route('addresses', ['kembali' => 'checkout']) }}" wire:navigate class="btn-primary btn-sm mt-4">Tambah alamat</a>
                    </div>
                @else
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($this->addresses as $address)
                            <label wire:key="ca-{{ $address->id }}"
                                   @class(['card relative cursor-pointer p-4 transition-colors duration-150',
                                       'border-ink' => $address->id === $addressId,
                                       'hover:border-ink/30' => $address->id !== $addressId])>
                                <input type="radio" wire:model.live="addressId" value="{{ $address->id }}" class="sr-only">
                                <span class="flex items-center gap-2 text-[15px] font-medium">
                                    {{ $address->label }}
                                    @if ($address->is_default)
                                        <span class="rounded-md bg-ember-soft px-2 py-0.5 text-xs font-medium text-ember">Utama</span>
                                    @endif
                                </span>
                                <span class="mt-1 block text-sm text-ink">{{ $address->recipient }} · <span class="tabular-nums">{{ $address->phone }}</span></span>
                                <span class="mt-1 block text-sm leading-6 text-ink-2">{{ $address->fullText() }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('addressId') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
            </section>

            {{-- 2. Kurir --}}
            <section>
                <h2 class="text-lg font-medium">Kurir</h2>
                <p class="meta mt-1 tabular-nums">Total berat {{ number_format($this->summary->totalWeight, 0, ',', '.') }} g</p>

                <div class="mt-4" wire:loading.class="opacity-60" wire:target="addressId">
                    @if (! $this->address)
                        <p class="text-sm text-ink-2">Pilih alamat untuk melihat ongkir.</p>
                    @elseif ($this->shippingOptions['error'])
                        <div class="card flex flex-wrap items-center justify-between gap-3 p-4">
                            <p class="text-sm text-red-800">{{ $this->shippingOptions['error'] }}</p>
                            <button type="button" wire:click="$refresh" class="btn-secondary btn-sm">Coba lagi</button>
                        </div>
                    @elseif (empty($this->shippingOptions['options']))
                        <p class="text-sm text-ink-2">Belum ada kurir yang melayani alamat ini.</p>
                    @else
                        <div class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-paper-card">
                            @foreach ($this->shippingOptions['options'] as $option)
                                <label wire:key="ship-{{ $option->key() }}" class="flex cursor-pointer items-center gap-4 px-4 py-3.5 transition-colors duration-150 hover:bg-paper-raised/60">
                                    <input type="radio" wire:model.live="shippingKey" value="{{ $option->key() }}" class="size-4 border-ink/30 text-ember focus:ring-ember/30">
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[15px]">{{ $option->courierLabel() }} <span class="text-ink-2">{{ $option->service }}</span></span>
                                        <span class="meta block">{{ $option->description }}@if ($option->etd) · {{ $option->etd }}@endif</span>
                                    </span>
                                    <span class="price text-[15px]">{{ rupiah($option->cost) }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
                @error('shippingKey') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
            </section>

            {{-- 3. Barang --}}
            <section>
                <h2 class="text-lg font-medium">Barang</h2>
                <ul class="mt-4 divide-y divide-line border-y border-line">
                    @foreach ($this->summary->items as $item)
                        <li class="flex items-center gap-4 py-3" wire:key="co-{{ $item->id }}">
                            <x-shop.product-image :product="$item->variant->product" class="aspect-square w-14 shrink-0 rounded-lg" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[15px]">{{ $item->variant->product->name }}</p>
                                <p class="meta tabular-nums">{{ $item->variant->label() }} · {{ $item->qty }} x {{ rupiah($item->variant->price) }}</p>
                            </div>
                            <p class="price text-[15px]">{{ rupiah($item->variant->price * $item->qty) }}</p>
                        </li>
                    @endforeach
                </ul>
                @if ($this->summary->hasProblems)
                    <p class="mt-3 text-sm text-red-700">Ada barang yang stoknya berubah. <a href="{{ route('cart') }}" wire:navigate class="underline">Cek keranjang</a></p>
                @endif
            </section>
        </div>

        {{-- Ringkasan --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="card p-6">
                <h2 class="text-lg font-medium">Ringkasan</h2>

                <div class="mt-4">
                    @if ($voucherCode && $this->voucher)
                        <div class="flex items-center justify-between rounded-lg bg-ember-soft px-3 py-2 text-sm">
                            <span class="font-medium text-ember">{{ $voucherCode }}</span>
                            <button type="button" wire:click="removeVoucher" class="text-ink-2 hover:text-ink">Hapus</button>
                        </div>
                    @else
                        <form wire:submit="applyVoucher" class="flex gap-2">
                            <label for="voucher" class="sr-only">Kode voucher</label>
                            <input id="voucher" type="text" wire:model="voucherInput" placeholder="Kode voucher" class="field h-10 uppercase placeholder:normal-case" autocomplete="off">
                            <button type="submit" class="btn-secondary h-10 px-4 text-sm">Pakai</button>
                        </form>
                    @endif
                    @error('voucherInput') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <dl class="mt-5 space-y-3 border-t border-line pt-5 text-[15px]">
                    <div class="flex justify-between">
                        <dt class="text-ink-2">Subtotal</dt>
                        <dd class="price">{{ rupiah($this->summary->subtotal) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-2">Ongkir</dt>
                        <dd class="price">{{ $this->selectedShipping ? rupiah($this->selectedShipping->cost) : '–' }}</dd>
                    </div>
                    @if ($this->discount > 0)
                        <div class="flex justify-between">
                            <dt class="text-ink-2">Diskon</dt>
                            <dd class="price text-green-700">−{{ rupiah($this->discount) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-line pt-3 text-lg">
                        <dt>Total</dt>
                        <dd class="price font-medium" data-testid="total">{{ rupiah($this->summary->subtotal - $this->discount + ($this->selectedShipping?->cost ?? 0)) }}</dd>
                    </div>
                </dl>

                <button type="button" wire:click="placeOrder" wire:loading.attr="disabled" wire:target="placeOrder"
                        class="btn-primary mt-6 w-full" @disabled(! $this->selectedShipping || $this->summary->hasProblems)>
                    <span wire:loading.remove wire:target="placeOrder">Bayar Sekarang</span>
                    <span wire:loading wire:target="placeOrder">Memproses</span>
                </button>
                <p class="meta mt-3">Stok dikunci setelah pesanan dibuat. Selesaikan pembayaran dalam 24 jam.</p>
            </div>
        </aside>
    </div>
</div>
