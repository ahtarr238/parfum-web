<?php

use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Exceptions\VoucherException;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Voucher;
use App\Services\OrderService;
use App\Support\FakeRajaOngkir;

beforeEach(function () {
    config(['services.rajaongkir.base_url' => 'https://rajaongkir.test/api/v1']);
    FakeRajaOngkir::register('https://rajaongkir.test/api/v1');

    $this->user = User::factory()->create();
    $this->address = Address::factory()->for($this->user)->create([
        'district_id' => 1001, 'district_name' => 'COBLONG', 'city_name' => 'BANDUNG', 'recipient' => 'Budi',
    ]);
    $this->variant = ProductVariant::factory()
        ->for(Product::factory()->state(['name' => 'Hutan Pagi']))
        ->create(['size_ml' => 50, 'price' => 200000, 'stock' => 5, 'weight_gram' => 400]);
    CartItem::factory()->for($this->user)->create(['product_variant_id' => $this->variant->id, 'qty' => 2]);
    $this->orders = app(OrderService::class);
});

it('membuat order, mengurangi stok, menyalin snapshot, dan mengosongkan keranjang', function () {
    $this->travelTo(now()->startOfMinute());

    $order = $this->orders->place($this->user, $this->address, 'jne|REG');

    // Fake RajaOngkir zona 1: (8000 + 4000) x 1 kg
    expect($order->status)->toBe(OrderStatus::PendingPayment)
        ->and($order->subtotal)->toBe(400000)
        ->and($order->shipping_cost)->toBe(12000)
        ->and($order->total)->toBe(412000)
        ->and($order->courier)->toBe('jne')
        ->and($order->courier_service)->toBe('REG')
        ->and($order->address_snapshot['recipient'])->toBe('Budi')
        ->and($order->address_snapshot['district_name'])->toBe('COBLONG')
        ->and($order->expires_at->equalTo(now()->addDay()))->toBeTrue()
        ->and($order->code)->toMatch('/^INV-\d{8}-[A-Z0-9]{5}$/');

    $item = $order->items()->first();
    expect([$item->product_name, $item->size_ml, $item->price, $item->qty])->toBe(['Hutan Pagi', 50, 200000, 2])
        ->and($this->variant->fresh()->stock)->toBe(3)
        ->and($this->user->cartItems()->count())->toBe(0);
});

it('snapshot tidak berubah saat produk diedit setelahnya', function () {
    $order = $this->orders->place($this->user, $this->address, 'jne|REG');

    $this->variant->update(['price' => 999000]);
    $this->variant->product->update(['name' => 'Nama Baru']);
    $this->address->update(['recipient' => 'Orang Lain']);

    $item = $order->fresh()->items()->first();
    expect($item->price)->toBe(200000)
        ->and($item->product_name)->toBe('Hutan Pagi')
        ->and($order->fresh()->address_snapshot['recipient'])->toBe('Budi');
});

it('gagal tanpa mengubah apa pun jika stok tidak cukup', function () {
    $other = ProductVariant::factory()->create(['stock' => 1, 'price' => 100000]);
    CartItem::factory()->for($this->user)->create(['product_variant_id' => $other->id, 'qty' => 1]);
    $other->update(['stock' => 0]);

    expect(fn () => $this->orders->place($this->user, $this->address, 'jne|REG'))
        ->toThrow(OrderException::class, 'stok');

    expect($this->variant->fresh()->stock)->toBe(5)
        ->and($this->user->orders()->count())->toBe(0)
        ->and($this->user->cartItems()->count())->toBe(2);
});

it('menolak keranjang kosong', function () {
    $this->user->cartItems()->delete();

    expect(fn () => $this->orders->place($this->user, $this->address, 'jne|REG'))
        ->toThrow(OrderException::class, 'Keranjang kosong');
});

it('menolak layanan kurir yang tidak ditawarkan untuk tujuan ini', function () {
    expect(fn () => $this->orders->place($this->user, $this->address, 'jne|PALSU'))
        ->toThrow(OrderException::class, 'kurir');
});

it('menolak alamat milik user lain', function () {
    $foreign = Address::factory()->create();

    expect(fn () => $this->orders->place($this->user, $foreign, 'jne|REG'))
        ->toThrow(OrderException::class);
});

it('menolak produk yang dinonaktifkan setelah masuk keranjang', function () {
    $this->variant->product->update(['is_active' => false]);

    expect(fn () => $this->orders->place($this->user, $this->address, 'jne|REG'))
        ->toThrow(OrderException::class);
    expect($this->variant->fresh()->stock)->toBe(5);
});

it('menerapkan voucher dan memakai kuotanya', function () {
    $voucher = Voucher::factory()->percent(10)->create(['code' => 'WANGI10', 'quota' => 5]);

    $order = $this->orders->place($this->user, $this->address, 'jne|REG', 'wangi10');

    expect($order->discount)->toBe(40000)
        ->and($order->total)->toBe(400000 - 40000 + 12000)
        ->and($order->voucher_id)->toBe($voucher->id)
        ->and($voucher->fresh()->used)->toBe(1);
});

it('voucher tidak valid membatalkan order tanpa mengurangi stok', function () {
    Voucher::factory()->create(['code' => 'MAHAL', 'min_purchase' => 10000000]);

    expect(fn () => $this->orders->place($this->user, $this->address, 'jne|REG', 'MAHAL'))
        ->toThrow(VoucherException::class);

    expect($this->variant->fresh()->stock)->toBe(5)
        ->and($this->user->cartItems()->count())->toBe(1);
});
