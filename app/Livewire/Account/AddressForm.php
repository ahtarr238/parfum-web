<?php

namespace App\Livewire\Account;

use App\Exceptions\ShippingException;
use App\Models\Address;
use App\Services\AddressBook;
use App\Services\ShippingService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AddressForm extends Component
{
    #[Locked]
    public ?int $addressId = null;

    public string $label = 'Rumah';

    public string $recipient = '';

    public string $phone = '';

    public ?int $province_id = null;

    public ?int $city_id = null;

    public ?int $district_id = null;

    public string $detail = '';

    public string $postal_code = '';

    public bool $is_default = false;

    public ?string $regionError = null;

    public function mount(?int $addressId = null): void
    {
        if ($addressId) {
            $address = $this->ownedAddress($addressId);
            $this->addressId = $address->id;
            $this->fill($address->only([
                'label', 'recipient', 'phone', 'province_id', 'city_id', 'district_id', 'detail', 'postal_code', 'is_default',
            ]));
        } else {
            $user = auth()->user();
            $this->recipient = (string) $user->name;
            $this->phone = (string) $user->phone;
        }
    }

    public function updatedProvinceId(): void
    {
        $this->city_id = null;
        $this->district_id = null;
    }

    public function updatedCityId(): void
    {
        $this->district_id = null;
    }

    #[Computed]
    public function provinces(): array
    {
        return $this->regions(fn (ShippingService $s) => $s->provinces());
    }

    #[Computed]
    public function cities(): array
    {
        return $this->province_id ? $this->regions(fn (ShippingService $s) => $s->cities($this->province_id)) : [];
    }

    #[Computed]
    public function districts(): array
    {
        return $this->city_id ? $this->regions(fn (ShippingService $s) => $s->districts($this->city_id)) : [];
    }

    public function save(AddressBook $book): void
    {
        $data = $this->validate([
            'label' => ['required', 'string', 'max:50'],
            'recipient' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,19}$/'],
            'province_id' => ['required', 'integer', Rule::in(array_keys($this->provinces))],
            'city_id' => ['required', 'integer', Rule::in(array_keys($this->cities))],
            'district_id' => ['required', 'integer', Rule::in(array_keys($this->districts))],
            'detail' => ['required', 'string', 'max:500'],
            'postal_code' => ['required', 'digits:5'],
            'is_default' => ['boolean'],
        ], attributes: [
            'label' => 'label',
            'recipient' => 'nama penerima',
            'phone' => 'no. HP',
            'province_id' => 'provinsi',
            'city_id' => 'kota/kabupaten',
            'district_id' => 'kecamatan',
            'detail' => 'alamat lengkap',
            'postal_code' => 'kode pos',
        ]);

        $data += [
            'province_name' => $this->provinces[$data['province_id']],
            'city_name' => $this->cities[$data['city_id']],
            'district_name' => $this->districts[$data['district_id']],
        ];

        $address = $this->addressId
            ? $book->update($this->ownedAddress($this->addressId), $data)
            : $book->add(auth()->user(), $data);

        $this->dispatch('address-saved', id: $address->id);
    }

    public function render(): View
    {
        return view('livewire.account.address-form');
    }

    private function ownedAddress(int $id): Address
    {
        return auth()->user()->addresses()->findOrFail($id);
    }

    /**
     * @param  callable(ShippingService): array<int, string>  $fetch
     * @return array<int, string>
     */
    private function regions(callable $fetch): array
    {
        try {
            return $fetch(app(ShippingService::class));
        } catch (ShippingException $e) {
            $this->regionError = 'Daftar wilayah belum bisa dimuat. Coba muat ulang halaman.';

            return [];
        }
    }
}
