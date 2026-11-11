<form wire:submit="save" class="space-y-5">
    @if ($regionError)
        <p class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">{{ $regionError }}</p>
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="addr-label" class="label">Label</label>
            <input id="addr-label" type="text" wire:model="label" class="field h-10" placeholder="Rumah, Kantor">
            @error('label') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="addr-recipient" class="label">Nama penerima</label>
            <input id="addr-recipient" type="text" wire:model="recipient" class="field h-10" autocomplete="name">
            @error('recipient') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label for="addr-phone" class="label">No. HP</label>
            <input id="addr-phone" type="tel" inputmode="tel" wire:model="phone" class="field h-10" autocomplete="tel" placeholder="08xxxxxxxxxx">
            @error('phone') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="addr-province" class="label">Provinsi</label>
            <select id="addr-province" wire:model.live="province_id" class="field h-10">
                <option value="">Pilih provinsi</option>
                @foreach ($this->provinces as $id => $name)
                    <option value="{{ $id }}">{{ region_name($name) }}</option>
                @endforeach
            </select>
            @error('province_id') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="addr-city" class="label">Kota/kabupaten</label>
            <select id="addr-city" wire:model.live="city_id" class="field h-10" @disabled(! $province_id)>
                <option value="">Pilih kota/kabupaten</option>
                @foreach ($this->cities as $id => $name)
                    <option value="{{ $id }}">{{ region_name($name) }}</option>
                @endforeach
            </select>
            @error('city_id') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="addr-district" class="label">Kecamatan</label>
            <select id="addr-district" wire:model.live="district_id" class="field h-10" @disabled(! $city_id)>
                <option value="">Pilih kecamatan</option>
                @foreach ($this->districts as $id => $name)
                    <option value="{{ $id }}">{{ region_name($name) }}</option>
                @endforeach
            </select>
            @error('district_id') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="addr-postal" class="label">Kode pos</label>
            <input id="addr-postal" type="text" inputmode="numeric" maxlength="5" wire:model="postal_code" class="field h-10 tabular-nums" autocomplete="postal-code">
            @error('postal_code') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label for="addr-detail" class="label">Alamat lengkap</label>
            <textarea id="addr-detail" wire:model="detail" rows="3" class="field" placeholder="Nama jalan, nomor rumah, RT/RW, patokan" autocomplete="street-address"></textarea>
            @error('detail') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
    </div>

    <label class="flex items-center gap-3 text-[15px] text-ink-2">
        <input type="checkbox" wire:model="is_default" class="size-4 rounded border-ink/30 text-ember focus:ring-ember/30">
        Jadikan alamat utama
    </label>

    <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">Simpan alamat</button>
        <button type="button" wire:click="$parent.closeForm" class="btn-secondary">Batal</button>
    </div>
</form>
