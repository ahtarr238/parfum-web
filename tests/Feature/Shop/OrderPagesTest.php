<?php

use App\Enums\OrderStatus;
use App\Livewire\Orders\OrderIndex;
use App\Livewire\Orders\OrderShow;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('daftar pesanan hanya menampilkan milik sendiri dan bisa difilter status', function () {
    $mine = Order::factory()->for($this->user)->create(['code' => 'INV-20261111-AAAAA']);
    $shipped = Order::factory()->for($this->user)->status(OrderStatus::Shipped)->create(['code' => 'INV-20261111-BBBBB']);
    Order::factory()->create(['code' => 'INV-20261111-CCCCC']);

    Livewire::actingAs($this->user)->test(OrderIndex::class)
        ->assertSee('INV-20261111-AAAAA')
        ->assertSee('INV-20261111-BBBBB')
        ->assertDontSee('INV-20261111-CCCCC')
        ->set('status', 'shipped')
        ->assertDontSee('INV-20261111-AAAAA')
        ->assertSee('INV-20261111-BBBBB');
});

it('detail pesanan milik orang lain mengembalikan 404', function () {
    $other = Order::factory()->create();

    $this->actingAs($this->user)->get(route('orders.show', $other))->assertNotFound();
});

it('detail menampilkan status, resi, item, dan alamat snapshot', function () {
    $order = Order::factory()->for($this->user)->status(OrderStatus::Shipped)->create([
        'tracking_number' => 'JNE0001',
        'address_snapshot' => ['recipient' => 'Budi Santoso', 'phone' => '0812', 'detail' => 'Jl. Dago 1', 'district_name' => 'Coblong', 'city_name' => 'Bandung', 'province_name' => 'Jawa Barat', 'postal_code' => '40135'],
    ]);
    OrderItem::factory()->for($order)->create(['product_name' => 'Hutan Pagi', 'size_ml' => 50]);

    $this->actingAs($this->user)->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Dikirim')
        ->assertSee('JNE0001')
        ->assertSee('Hutan Pagi')
        ->assertSee('Budi Santoso')
        ->assertSee('Pesanan Diterima');
});

it('customer menekan pesanan diterima', function () {
    $order = Order::factory()->for($this->user)->status(OrderStatus::Shipped)->create();

    Livewire::actingAs($this->user)->test(OrderShow::class, ['order' => $order])
        ->call('confirmReceived')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
});

it('customer bisa membatalkan pesanan yang belum dibayar dan stok kembali', function () {
    $variant = ProductVariant::factory()->create(['stock' => 1]);
    $order = Order::factory()->for($this->user)->create();
    OrderItem::factory()->for($order)->create(['product_variant_id' => $variant->id, 'qty' => 2]);

    Livewire::actingAs($this->user)->test(OrderShow::class, ['order' => $order])
        ->call('cancel');

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($variant->fresh()->stock)->toBe(3);
});

it('customer tidak bisa membatalkan pesanan yang sudah dibayar', function () {
    $order = Order::factory()->for($this->user)->status(OrderStatus::Paid)->create();

    Livewire::actingAs($this->user)->test(OrderShow::class, ['order' => $order])
        ->call('cancel')
        ->assertHasErrors('action');

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('email pengiriman berisi nomor resi', function () {
    $order = Order::factory()->for($this->user)->status(OrderStatus::Shipped)->create(['tracking_number' => 'SICEPAT777']);
    OrderItem::factory()->for($order)->create();

    $html = (new OrderShippedMail($order))->render();

    expect($html)->toContain('SICEPAT777')->toContain($order->code);
});
