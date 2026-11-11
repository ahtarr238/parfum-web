<?php

use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Livewire\Orders\OrderShow;
use App\Mail\PaymentReceivedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Livewire\Volt\Volt;

// Temuan #1: menyimpan produk tidak boleh menimpa stok yang berubah sejak form dibuka.
it('menyimpan produk di Filament tidak menimpa stok terbaru', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['description' => 'lama']);
    $variant = ProductVariant::factory()->for($product)->create(['stock' => 10]);

    $form = Livewire::actingAs($admin)->test(EditProduct::class, ['record' => $product->slug]);

    $variant->decrement('stock', 3); // pesanan masuk saat form terbuka

    $form->set('data.description', 'baru')->call('save')->assertHasNoFormErrors();

    expect($variant->fresh()->stock)->toBe(7)
        ->and($product->fresh()->description)->toBe('baru');
});

it('admin menyesuaikan stok secara atomik lewat aksi Ubah stok', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create(['stock' => 4]);

    Livewire::actingAs($admin)->test(EditProduct::class, ['record' => $product->slug])
        ->callAction('adjustStock', data: ['variant_id' => $variant->id, 'change' => 10])
        ->assertHasNoActionErrors();
    expect($variant->fresh()->stock)->toBe(14);

    Livewire::actingAs($admin)->test(EditProduct::class, ['record' => $product->slug])
        ->callAction('adjustStock', data: ['variant_id' => $variant->id, 'change' => -20]);
    expect($variant->fresh()->stock)->toBe(14);
});

// Temuan #2: pembatalan oleh customer/kedaluwarsa hanya untuk order yang masih Menunggu Bayar.
it('cancelUnpaid menolak order yang sudah dibayar', function () {
    $variant = ProductVariant::factory()->create(['stock' => 0]);
    $order = Order::factory()->status(OrderStatus::Paid)->create(['expires_at' => now()->subHour()]);
    OrderItem::factory()->for($order)->create(['product_variant_id' => $variant->id, 'qty' => 2]);

    expect(fn () => app(OrderService::class)->cancelUnpaid($order))->toThrow(OrderException::class);
    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($variant->fresh()->stock)->toBe(0);
});

it('customer tidak membatalkan order yang baru dibayar setelah halaman dibuka', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    $page = Livewire::actingAs($user)->test(OrderShow::class, ['order' => $order]);
    $order->forceFill(['status' => OrderStatus::Paid])->save(); // webhook masuk

    $page->call('cancel')->assertHasErrors('action');
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('expire tidak membatalkan order yang batas waktunya diperpanjang', function () {
    $order = Order::factory()->create(['expires_at' => now()->addHour()]);

    expect(fn () => app(OrderService::class)->expire($order))->toThrow(OrderException::class);
    expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

// Temuan #5: notifikasi webhook yang diulang tidak mengirim email dua kali.
it('markPaid yang diulang hanya mengirim satu email', function () {
    Mail::fake();
    $order = Order::factory()->create();

    app(OrderService::class)->markPaid($order, []);
    app(OrderService::class)->markPaid($order->fresh(), []);

    Mail::assertQueued(PaymentReceivedMail::class, 1);
});

// Temuan #6: semua order kedaluwarsa diproses walau banyak.
it('orders:expire memproses semua order kedaluwarsa', function () {
    Order::factory()->count(5)->create(['expires_at' => now()->subMinute()]);

    expect(app(OrderService::class)->expireOverdue())->toBe(5)
        ->and(Order::where('status', OrderStatus::PendingPayment)->count())->toBe(0);
});

// Temuan #7: customer dengan riwayat pesanan tidak bisa menghapus akun (FK), harus pesan jelas, bukan error 500.
it('hapus akun dengan riwayat pesanan ditolak dengan pesan jelas', function () {
    $user = User::factory()->create();
    Order::factory()->for($user)->create();

    Volt::actingAs($user)->test('profile.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasErrors('password');

    expect(User::find($user->id))->not->toBeNull();
});
