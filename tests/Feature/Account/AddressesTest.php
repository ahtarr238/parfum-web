<?php

use App\Livewire\Account\Addresses;
use App\Livewire\Account\AddressForm;
use App\Models\Address;
use App\Models\User;
use App\Services\AddressBook;
use App\Support\FakeRajaOngkir;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.rajaongkir.base_url' => 'https://rajaongkir.test/api/v1']);
    FakeRajaOngkir::register('https://rajaongkir.test/api/v1');
    $this->user = User::factory()->create();
});

it('halaman alamat butuh login', function () {
    $this->get('/akun/alamat')->assertRedirect('/login');
    $this->actingAs($this->user)->get('/akun/alamat')->assertOk()->assertSee('Alamat');
});

it('dropdown kota dan kecamatan mengikuti pilihan di atasnya', function () {
    Livewire::actingAs($this->user)->test(AddressForm::class)
        ->assertSee('Jawa Barat')
        ->set('province_id', 9)
        ->assertSee('Bandung')
        ->set('city_id', 23)
        ->assertSee('Coblong')
        ->set('province_id', 6)
        ->assertSet('city_id', null)
        ->assertSet('district_id', null);
});

it('menyimpan alamat baru dengan nama wilayah dari RajaOngkir', function () {
    Livewire::actingAs($this->user)->test(AddressForm::class)
        ->set('label', 'Rumah')
        ->set('recipient', 'Budi')
        ->set('phone', '081234567890')
        ->set('province_id', 9)
        ->set('city_id', 23)
        ->set('district_id', 1001)
        ->set('detail', 'Jl. Dago No. 1')
        ->set('postal_code', '40135')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('address-saved');

    $address = $this->user->addresses()->first();

    expect($address->province_name)->toBe('JAWA BARAT')
        ->and($address->city_name)->toBe('BANDUNG')
        ->and($address->district_name)->toBe('COBLONG')
        ->and($address->is_default)->toBeTrue();
});

it('menolak kecamatan yang bukan milik kota terpilih', function () {
    Livewire::actingAs($this->user)->test(AddressForm::class)
        ->set('label', 'Rumah')->set('recipient', 'Budi')->set('phone', '081234567890')
        ->set('province_id', 9)->set('city_id', 23)
        ->set('district_id', 2101)
        ->set('detail', 'Jl. Dago')->set('postal_code', '40135')
        ->call('save')
        ->assertHasErrors('district_id');
});

it('mengubah alamat milik sendiri', function () {
    $address = app(AddressBook::class)->add($this->user, Address::factory()->raw(['province_id' => 9, 'city_id' => 23, 'district_id' => 1001]));

    Livewire::actingAs($this->user)->test(AddressForm::class, ['addressId' => $address->id])
        ->assertSet('recipient', $address->recipient)
        ->set('recipient', 'Siti')
        ->call('save')
        ->assertHasNoErrors();

    expect($address->fresh()->recipient)->toBe('Siti');
});

it('tidak bisa membuka alamat milik orang lain', function () {
    $other = Address::factory()->create();

    expect(fn () => Livewire::actingAs($this->user)->test(AddressForm::class, ['addressId' => $other->id]))
        ->toThrow(ModelNotFoundException::class);
});

it('menjadikan alamat utama dan menghapus dari daftar', function () {
    $book = app(AddressBook::class);
    $first = $book->add($this->user, Address::factory()->raw());
    $second = $book->add($this->user, Address::factory()->raw());

    Livewire::actingAs($this->user)->test(Addresses::class)
        ->call('makeDefault', $second->id)
        ->call('delete', $first->id);

    expect($second->fresh()->is_default)->toBeTrue()
        ->and(Address::find($first->id))->toBeNull();
});

it('tidak bisa menghapus alamat milik orang lain', function () {
    $other = Address::factory()->create();

    expect(fn () => Livewire::actingAs($this->user)->test(Addresses::class)->call('delete', $other->id))
        ->toThrow(ModelNotFoundException::class);

    expect(Address::find($other->id))->not->toBeNull();
});
