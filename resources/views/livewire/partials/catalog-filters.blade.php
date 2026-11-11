<div class="space-y-8">
    <div>
        <label for="{{ $prefix }}-q" class="label">Cari</label>
        <input id="{{ $prefix }}-q" type="search" wire:model.live.debounce.400ms="q" placeholder="Nama atau brand" class="field h-10 text-sm">
    </div>

    @unless ($this->lockedCategory)
        <fieldset>
            <legend class="label">Kategori</legend>
            <div class="space-y-1">
                <label class="flex h-8 cursor-pointer items-center gap-3 text-[15px] text-ink-2 hover:text-ink">
                    <input type="radio" wire:model.live="category" value="" class="size-4 border-ink/30 text-ember focus:ring-ember/30">
                    Semua
                </label>
                @foreach ($categories as $cat)
                    <label class="flex h-8 cursor-pointer items-center gap-3 text-[15px] text-ink-2 hover:text-ink">
                        <input type="radio" wire:model.live="category" value="{{ $cat->slug }}" class="size-4 border-ink/30 text-ember focus:ring-ember/30">
                        {{ $cat->name }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endunless

    <fieldset>
        <legend class="label">Aroma</legend>
        <div class="space-y-1">
            @foreach ($scentOptions as $scent)
                <label class="flex h-8 cursor-pointer items-center gap-3 text-[15px] text-ink-2 hover:text-ink">
                    <input type="checkbox" wire:model.live="scents" value="{{ $scent->value }}" class="size-4 rounded border-ink/30 text-ember focus:ring-ember/30">
                    <x-shop.scent-dot :family="$scent" />
                    {{ $scent->label() }}
                </label>
            @endforeach
        </div>
    </fieldset>

    @unless ($this->lockedBrand)
        <fieldset>
            <legend class="label">Brand</legend>
            <div class="space-y-1">
                @foreach ($brandOptions as $brandOption)
                    <label class="flex h-8 cursor-pointer items-center gap-3 text-[15px] text-ink-2 hover:text-ink">
                        <input type="checkbox" wire:model.live="brands" value="{{ $brandOption->slug }}" class="size-4 rounded border-ink/30 text-ember focus:ring-ember/30">
                        {{ $brandOption->name }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endunless

    <fieldset>
        <legend class="label">Ukuran</legend>
        <div class="flex flex-wrap gap-2">
            @foreach ($sizeOptions as $size)
                <label class="cursor-pointer">
                    <input type="checkbox" wire:model.live="sizes" value="{{ $size }}" class="peer sr-only">
                    <span class="inline-flex h-8 items-center rounded-lg border border-line bg-paper-card px-3 text-sm tabular-nums text-ink-2 transition-colors duration-150 peer-checked:border-ink peer-checked:text-ink peer-focus-visible:ring-2 peer-focus-visible:ring-ember/60">{{ $size }}ml</span>
                </label>
            @endforeach
            <label class="cursor-pointer">
                <input type="checkbox" wire:model.live="sizes" value="decant" class="peer sr-only">
                <span class="inline-flex h-8 items-center rounded-lg border border-line bg-paper-card px-3 text-sm text-ink-2 transition-colors duration-150 peer-checked:border-ink peer-checked:text-ink peer-focus-visible:ring-2 peer-focus-visible:ring-ember/60">Decant</span>
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend class="label">Harga</legend>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label for="{{ $prefix }}-min" class="sr-only">Harga minimum</label>
                <input id="{{ $prefix }}-min" type="text" inputmode="numeric" wire:model.live.debounce.500ms="min" placeholder="Min" class="field h-10 text-sm tabular-nums">
            </div>
            <div>
                <label for="{{ $prefix }}-max" class="sr-only">Harga maksimum</label>
                <input id="{{ $prefix }}-max" type="text" inputmode="numeric" wire:model.live.debounce.500ms="max" placeholder="Maks" class="field h-10 text-sm tabular-nums">
            </div>
        </div>
        <p class="meta mt-2">Dalam Rupiah, misalnya 200000</p>
    </fieldset>
</div>
