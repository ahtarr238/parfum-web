<div x-data="{ sheet: false }">
    <div class="page pt-10">
        @if ($this->lockedBrand)
            <p class="meta">Brand</p>
        @elseif ($this->lockedCategory)
            <p class="meta">Kategori</p>
        @endif
        <div class="mt-1 flex flex-wrap items-end justify-between gap-4">
            <h1 class="font-serif text-4xl leading-tight text-ink sm:text-5xl">{{ $heading }}</h1>
            <p class="meta tabular-nums" data-testid="result-count">{{ $products->total() }} parfum</p>
        </div>
    </div>

    <div class="page mt-8 flex items-center gap-3 border-y border-line py-3 lg:hidden">
        <button type="button" @click="sheet = true" class="btn-secondary btn-sm">
            <x-lucide-sliders-horizontal class="size-4" stroke-width="1.5" />
            Filter
            @if ($this->activeFilterCount)
                <span class="tabular-nums text-ink-3">{{ $this->activeFilterCount }}</span>
            @endif
        </button>
        <label for="sort-mobile" class="sr-only">Urutkan</label>
        <select id="sort-mobile" wire:model.live="sort" class="field ms-auto h-9 w-auto py-0 text-sm">
            @foreach (\App\Queries\CatalogQuery::SORTS as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="page mt-6 lg:mt-10 lg:grid lg:grid-cols-[240px_1fr] lg:gap-12">
        <aside class="hidden lg:block">
            @include('livewire.partials.catalog-filters', ['prefix' => 'd'])
        </aside>

        <section>
            <div class="mb-6 hidden items-center justify-between lg:flex">
                @if ($this->activeFilterCount || $q !== '')
                    <button type="button" wire:click="resetFilters" class="text-sm text-ink-2 underline decoration-ink/20 underline-offset-4 hover:text-ink">Hapus semua filter</button>
                @else
                    <span></span>
                @endif
                <div class="flex items-center gap-3">
                    <label for="sort" class="text-sm text-ink-3">Urutkan</label>
                    <select id="sort" wire:model.live="sort" class="field h-9 w-auto py-0 text-sm">
                        @foreach (\App\Queries\CatalogQuery::SORTS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div wire:loading.class="opacity-60" class="transition-opacity duration-150">
                @if ($products->isEmpty())
                    <div class="card flex flex-col items-start gap-4 p-8">
                        <p class="text-lg text-ink">Tidak ada parfum yang cocok</p>
                        <p class="text-sm text-ink-2">Coba kurangi filter atau cari dengan kata lain.</p>
                        <button type="button" wire:click="resetFilters" class="btn-secondary btn-sm">Hapus filter</button>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-x-4 gap-y-10 sm:grid-cols-3 sm:gap-x-6">
                        @foreach ($products as $product)
                            <x-shop.product-card :product="$product" wire:key="p-{{ $product->id }}" />
                        @endforeach
                    </div>

                    <div class="mt-12">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </section>
    </div>

    {{-- Panel filter mobile (bottom sheet) --}}
    <div x-cloak x-show="sheet" class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="Filter">
        <div x-show="sheet" x-transition.opacity.duration.200ms @click="sheet = false" class="absolute inset-0 bg-ink/30"></div>
        <div x-show="sheet"
             x-transition:enter="transition-transform duration-300 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="transition-transform duration-200 ease-out" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
             class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-y-auto rounded-t-2xl bg-paper-card px-4 pb-6 pt-4">
            <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-ink/15"></div>
            <div class="mb-4 flex items-center justify-between">
                <p class="text-lg font-medium">Filter</p>
                <button type="button" wire:click="resetFilters" class="text-sm text-ink-2 underline decoration-ink/20 underline-offset-4">Hapus semua</button>
            </div>
            @include('livewire.partials.catalog-filters', ['prefix' => 'm'])
            <button type="button" @click="sheet = false" class="btn-primary mt-6 w-full">
                Lihat {{ $products->total() }} parfum
            </button>
        </div>
    </div>
</div>
