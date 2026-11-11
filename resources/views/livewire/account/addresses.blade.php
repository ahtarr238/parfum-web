<div class="page max-w-3xl pt-10">
    @include('partials.account-nav')

    <div class="mt-8 flex items-center justify-between gap-4">
        <h1 class="font-serif text-4xl">Alamat</h1>
        @unless ($showForm)
            <button type="button" wire:click="create" class="btn-secondary btn-sm">
                <x-lucide-plus class="size-4" stroke-width="1.5" /> Tambah alamat
            </button>
        @endunless
    </div>

    @if (session('status'))
        <p class="mt-4 text-sm text-ink-2" role="status">{{ session('status') }}</p>
    @endif

    @if ($showForm)
        <div class="card mt-6 p-6">
            <h2 class="mb-5 text-lg font-medium">{{ $editingId ? 'Ubah alamat' : 'Alamat baru' }}</h2>
            <livewire:account.address-form :address-id="$editingId" :key="'form-'.($editingId ?? 'new')" />
        </div>
    @endif

    <div class="mt-6 space-y-3">
        @forelse ($addresses as $address)
            <div class="card p-5" wire:key="addr-{{ $address->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="flex items-center gap-2 text-[15px] font-medium">
                            {{ $address->label }}
                            @if ($address->is_default)
                                <span class="rounded-md bg-ember-soft px-2 py-0.5 text-xs font-medium text-ember">Utama</span>
                            @endif
                        </p>
                        <p class="mt-2 text-sm text-ink">{{ $address->recipient }} · <span class="tabular-nums">{{ $address->phone }}</span></p>
                        <p class="mt-1 max-w-md text-sm leading-6 text-ink-2">{{ $address->fullText() }}</p>
                    </div>
                    <div class="flex items-center gap-1">
                        @unless ($address->is_default)
                            <button type="button" wire:click="makeDefault({{ $address->id }})" class="h-8 rounded-lg px-3 text-sm text-ink-2 hover:bg-ink/5 hover:text-ink">Jadikan utama</button>
                        @endunless
                        <button type="button" wire:click="edit({{ $address->id }})" class="h-8 rounded-lg px-3 text-sm text-ink-2 hover:bg-ink/5 hover:text-ink">Ubah</button>
                        <button type="button" wire:click="delete({{ $address->id }})" wire:confirm="Hapus alamat {{ $address->label }}?" class="h-8 rounded-lg px-3 text-sm text-ink-2 hover:bg-ink/5 hover:text-red-700">Hapus</button>
                    </div>
                </div>
            </div>
        @empty
            @unless ($showForm)
                <div class="card p-8">
                    <p class="text-ink">Belum ada alamat</p>
                    <p class="mt-1 text-sm text-ink-2">Tambahkan alamat supaya ongkir bisa dihitung saat checkout.</p>
                </div>
            @endunless
        @endforelse
    </div>

    @if ($redirectTo && $addresses->isNotEmpty())
        <a href="{{ $redirectTo }}" wire:navigate class="btn-primary mt-8">Lanjut ke checkout</a>
    @endif
</div>
