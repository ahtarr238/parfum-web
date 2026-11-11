@php
    use App\Enums\OrderStatus;
    $steps = [OrderStatus::PendingPayment, OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Completed];
    $current = array_search($order->status, $steps, true);
    $snap = $order->address_snapshot;
@endphp
<div class="page max-w-4xl pt-10">
    <a href="{{ route('orders.index') }}" wire:navigate class="meta inline-flex items-center gap-1 hover:text-ink">
        <x-lucide-arrow-left class="size-3.5" stroke-width="1.5" /> Pesanan saya
    </a>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-serif text-4xl tabular-nums">{{ $order->code }}</h1>
            <p class="meta mt-1">Dibuat {{ $order->created_at->translatedFormat('j F Y, H:i') }} WIB</p>
        </div>
        <x-shop.order-status :status="$order->status" class="h-8 px-3 text-sm" />
    </div>

    @if (session('order-placed'))
        <p class="mt-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-900" role="status">Pesanan dibuat dan stok sudah kami kunci untukmu.</p>
    @endif
    @if (session('status'))
        <p class="mt-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-900" role="status">{{ session('status') }}</p>
    @endif
    @error('action')
        <p class="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $message }}</p>
    @enderror

    {{-- Progres --}}
    @if ($order->status !== OrderStatus::Cancelled)
        <ol class="mt-8 grid grid-cols-5 gap-2" aria-label="Progres pesanan">
            @foreach ($steps as $i => $step)
                <li class="min-w-0">
                    <div @class(['h-1 rounded-full', 'bg-ember' => $i <= $current, 'bg-ink/10' => $i > $current])></div>
                    <p @class(['mt-2 truncate text-[13px]', 'text-ink' => $i === $current, 'text-ink-2' => $i < $current, 'text-ink-3' => $i > $current])>{{ $step->label() }}</p>
                </li>
            @endforeach
        </ol>
    @endif

    {{-- Aksi sesuai status --}}
    @if ($order->status === OrderStatus::PendingPayment)
        <div class="card mt-8 p-6">
            <p class="text-[15px] font-medium">Menunggu pembayaran</p>
            <p class="mt-1 text-sm text-ink-2">Bayar sebelum <span class="text-ink">{{ $order->expires_at->translatedFormat('j F Y, H:i') }} WIB</span>. Setelah itu pesanan dibatalkan otomatis dan stok dilepas.</p>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button type="button" disabled class="btn-primary">Bayar {{ rupiah($order->total) }}</button>
                <button type="button" wire:click="cancel" wire:confirm="Batalkan pesanan {{ $order->code }}?" class="btn-secondary">Batalkan pesanan</button>
            </div>
            <p class="meta mt-3">Pembayaran online lewat VA bank, QRIS, dan e-wallet sedang kami siapkan.</p>
        </div>
    @elseif ($order->status === OrderStatus::Shipped)
        <div class="card mt-8 flex flex-wrap items-center justify-between gap-4 p-6">
            <div>
                <p class="text-[15px] font-medium">Paket dalam perjalanan</p>
                <p class="mt-1 text-sm text-ink-2">Sudah sampai? Tandai diterima supaya kami tahu pesananmu aman.</p>
            </div>
            <button type="button" wire:click="confirmReceived" wire:confirm="Paket sudah kamu terima dengan baik?" class="btn-primary">Pesanan Diterima</button>
        </div>
    @endif

    <div class="mt-8 grid gap-6 md:grid-cols-2">
        <div class="card p-6">
            <h2 class="text-[15px] font-medium">Pengiriman</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-ink-2">Kurir</dt><dd>{{ strtoupper($order->courier) }} {{ $order->courier_service }}</dd></div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-2">No. resi</dt>
                    <dd class="tabular-nums">{{ $order->tracking_number ?? 'Belum ada' }}</dd>
                </div>
            </dl>
            <div class="mt-4 border-t border-line pt-4 text-sm leading-6">
                <p class="text-ink">{{ $snap['recipient'] ?? '' }} · <span class="tabular-nums">{{ $snap['phone'] ?? '' }}</span></p>
                <p class="text-ink-2">{{ $snap['detail'] ?? '' }}, {{ region_name($snap['district_name'] ?? '') }}, {{ region_name($snap['city_name'] ?? '') }}, {{ region_name($snap['province_name'] ?? '') }} {{ $snap['postal_code'] ?? '' }}</p>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-[15px] font-medium">Pembayaran</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-ink-2">Subtotal</dt><dd class="price">{{ rupiah($order->subtotal) }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-2">Ongkir</dt><dd class="price">{{ rupiah($order->shipping_cost) }}</dd></div>
                @if ($order->discount > 0)
                    <div class="flex justify-between"><dt class="text-ink-2">Diskon</dt><dd class="price text-green-700">−{{ rupiah($order->discount) }}</dd></div>
                @endif
                <div class="flex justify-between border-t border-line pt-2 text-[15px]"><dt>Total</dt><dd class="price font-medium">{{ rupiah($order->total) }}</dd></div>
                @if ($order->payment?->paid_at)
                    <div class="flex justify-between pt-1"><dt class="text-ink-2">Dibayar</dt><dd>{{ $order->payment->paid_at->translatedFormat('j M Y, H:i') }}</dd></div>
                @endif
            </dl>
        </div>
    </div>

    <section class="mt-8">
        <h2 class="text-lg font-medium">Barang</h2>
        <ul class="mt-4 divide-y divide-line border-y border-line">
            @foreach ($order->items as $item)
                <li class="flex flex-wrap items-center gap-4 py-4" wire:key="oi-{{ $item->id }}">
                    @if ($item->variant)
                        <x-shop.product-image :product="$item->variant->product" class="aspect-square w-14 shrink-0 rounded-lg" />
                    @else
                        <div class="aspect-square w-14 shrink-0 rounded-lg bg-paper-raised"></div>
                    @endif
                    <div class="min-w-0 flex-1">
                        @if ($item->variant)
                            <a href="{{ route('products.show', $item->variant->product) }}" wire:navigate class="text-[15px] hover:text-ember">{{ $item->product_name }}</a>
                        @else
                            <p class="text-[15px]">{{ $item->product_name }}</p>
                        @endif
                        <p class="meta tabular-nums">{{ $item->size_ml }}ml · {{ $item->qty }} x {{ rupiah($item->price) }}</p>
                    </div>
                    <p class="price text-[15px]">{{ rupiah($item->lineTotal()) }}</p>
                    @if ($order->status === OrderStatus::Completed)
                        <div class="w-full sm:w-auto">
                            @if ($item->review)
                                <span class="meta inline-flex items-center gap-1"><x-lucide-check class="size-3.5" stroke-width="1.5" /> Sudah diulas</span>
                            @elseif ($item->variant)
                                <livewire:reviews.review-form :order-item-id="$item->id" :key="'rf-'.$item->id" />
                            @endif
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
</div>
