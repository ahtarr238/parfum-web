<?php

use App\Livewire\Checkout;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Voucher;
use App\Support\FakeRajaOngkir;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.rajaongkir.base_url' => 'https://rajaongkir.test/api/v1']);
    FakeRajaOngkir::register('https://rajaongkir.test/api/v1');

    $this->user = User::factory()->create();
    $this->home = Address::factory()->for($this->user)->create(['label' => 'Rumah', 'district_id' => 1001, 'is_default' => true]);
    $this->bali = Address::factory()->for($this->user)->create(['label' => 'Vila', 'district_id' => 5101]);
    $variant = ProductVariant::factory()->create(['price' => 250000, 'stock' => 5, 'weight_gram' => 400]);
    CartItem::factory()->for($this->user)->create(['product_variant_id' => $variant->id, 'qty' => 1]);
});

it('checkout butuh login', function () {
    $this->get('/checkout')->assertRedirect('/login');
});

it('keranjang kosong diarahkan ke halaman keranjang', function () {
    $this->user->cartItems()->delete();

    Livewire::actingAs($this->user)->test(Checkout::class)->assertRedirect(route('cart'));
});

it('memakai alamat utama dan menampilkan pilihan kurir dari RajaOngkir', function () {
    Livewire::actingAs($this->user)->test(Checkout::class)
        ->assertSet('addressId', $this->home->id)
        ->assertSee('JNE')
        ->assertSee('SiCepat')
        ->assertSee('Rp12.000');
});

it('mengganti alamat memuat ulang ongkir dan mereset kurir', function () {
    Livewire::actingAs($this->user)->test(Checkout::class)
        ->set('shippingKey', 'jne|REG')
        ->set('addressId', $this->bali->id)
        ->assertSet('shippingKey', null)
        ->assertSee('Rp20.000'); // zona 3: 8000 + 4000 x 3
});

it('total ikut ongkir yang dipilih', function () {
    Livewire::actingAs($this->user)->test(Checkout::class)
        ->set('shippingKey', 'jne|YES') // zona 1: 16000 + 6000
        ->assertSee('Rp272.000');
});

it('voucher valid mengurangi total, tidak valid menampilkan pesan', function () {
    Voucher::factory()->create(['code' => 'HEMAT', 'value' => 50000]);

    Livewire::actingAs($this->user)->test(Checkout::class)
        ->set('shippingKey', 'jne|REG')
        ->set('voucherInput', 'asal')
        ->call('applyVoucher')
        ->assertHasErrors('voucherInput')
        ->set('voucherInput', 'hemat')
        ->call('applyVoucher')
        ->assertHasNoErrors()
        ->assertSet('voucherCode', 'HEMAT')
        ->assertSee('Rp212.000');
});

it('bayar sekarang membuat order dan mengarah ke halaman pesanan', function () {
    $component = Livewire::actingAs($this->user)->test(Checkout::class)
        ->set('shippingKey', 'jne|REG')
        ->call('placeOrder');

    $order = Order::first();
    $component->assertRedirect(route('orders.show', $order));

    expect($order->total)->toBe(262000)
        ->and($order->address_snapshot['label'])->toBe('Rumah');
});

it('tidak bisa membuat order tanpa memilih kurir', function () {
    Livewire::actingAs($this->user)->test(Checkout::class)
        ->call('placeOrder')
        ->assertHasErrors('shippingKey');

    expect(Order::count())->toBe(0);
});

it('tidak bisa memakai alamat milik user lain', function () {
    $foreign = Address::factory()->create();

    Livewire::actingAs($this->user)->test(Checkout::class)
        ->set('addressId', $foreign->id)
        ->set('shippingKey', 'jne|REG')
        ->call('placeOrder')
        ->assertHasErrors('addressId');

    expect(Order::count())->toBe(0);
});
