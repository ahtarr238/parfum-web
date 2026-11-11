<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Database\QueryException;

it('label status sesuai PRD', function () {
    expect(array_map(fn (OrderStatus $s) => $s->label(), OrderStatus::cases()))
        ->toBe(['Menunggu Bayar', 'Dibayar', 'Dikemas', 'Dikirim', 'Selesai', 'Dibatalkan']);
});

it('order baru berstatus menunggu bayar', function () {
    expect(Order::factory()->create()->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

it('item order tetap ada saat produk dihapus', function () {
    $item = OrderItem::factory()->create(['product_name' => 'Nama Parfum', 'size_ml' => 50, 'price' => 350000]);

    $item->variant->product->delete();
    $item->refresh();

    expect($item->product_variant_id)->toBeNull()
        ->and([$item->product_name, $item->size_ml, $item->price])->toBe(['Nama Parfum', 50, 350000]);
});

it('menyimpan snapshot alamat sebagai array', function () {
    $order = Order::factory()->create(['address_snapshot' => ['recipient' => 'Budi', 'city_name' => 'Bandung']]);

    expect($order->fresh()->address_snapshot['city_name'])->toBe('Bandung');
});

it('satu order hanya punya satu payment', function () {
    $order = Order::factory()->create();
    Payment::factory()->for($order)->create();

    expect(fn () => Payment::factory()->for($order)->create())->toThrow(QueryException::class);
});

it('menghubungkan order dengan item, payment, dan voucher', function () {
    $voucher = Voucher::factory()->create();
    $order = Order::factory()->for($voucher)->has(OrderItem::factory()->count(2), 'items')->create();
    Payment::factory()->for($order)->create();

    $order->refresh();

    expect($order->items)->toHaveCount(2)
        ->and($order->payment)->toBeInstanceOf(Payment::class)
        ->and($order->voucher->is($voucher))->toBeTrue()
        ->and($order->user->orders)->toHaveCount(1);
});

it('kode order unik', function () {
    Order::factory()->create(['code' => 'INV-20261111-AAAAA']);

    expect(fn () => Order::factory()->create(['code' => 'INV-20261111-AAAAA']))->toThrow(QueryException::class);
});
