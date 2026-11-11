<div class="page pt-10">
    @include('partials.account-nav')

    <h1 class="mt-8 font-serif text-4xl">Pesanan saya</h1>

    <div class="mt-6 flex gap-2 overflow-x-auto pb-1">
        @foreach (\App\Livewire\Orders\OrderIndex::TABS as $value => $label)
            <button type="button" wire:click="$set('status', '{{ $value }}')"
                @class(['h-9 shrink-0 rounded-lg border px-3 text-sm transition-colors duration-150',
                    'border-ink bg-ink text-paper-card' => $status === $value,
                    'border-line bg-paper-card text-ink-2 hover:text-ink' => $status !== $value])>{{ $label }}</button>
        @endforeach
    </div>

    <div class="mt-6 space-y-3" wire:loading.class="opacity-60">
        @forelse ($orders as $order)
            <a href="{{ route('orders.show', $order) }}" wire:navigate wire:key="o-{{ $order->id }}"
               class="card block p-5 transition-colors duration-150 hover:border-ink/25">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[15px] font-medium tabular-nums">{{ $order->code }}</p>
                        <p class="meta">{{ $order->created_at->translatedFormat('j F Y, H:i') }}</p>
                    </div>
                    <x-shop.order-status :status="$order->status" />
                </div>
                <div class="mt-4 flex flex-wrap items-end justify-between gap-3 border-t border-line pt-4">
                    <p class="text-sm text-ink-2">
                        {{ $order->items->first()?->product_name }} {{ $order->items->first()?->size_ml }}ml
                        @if ($order->items->count() > 1)
                            <span class="text-ink-3">+{{ $order->items->count() - 1 }} barang lain</span>
                        @endif
                    </p>
                    <p class="price text-[15px] font-medium">{{ rupiah($order->total) }}</p>
                </div>
            </a>
        @empty
            <div class="card p-8">
                <p class="text-lg">Belum ada pesanan</p>
                <p class="mt-1 text-sm text-ink-2">Pesanan yang kamu buat akan muncul di sini.</p>
                <a href="{{ route('catalog') }}" wire:navigate class="btn-primary btn-sm mt-4">Lihat katalog</a>
            </div>
        @endforelse
    </div>

    <div class="mt-8">{{ $orders->links() }}</div>
</div>
