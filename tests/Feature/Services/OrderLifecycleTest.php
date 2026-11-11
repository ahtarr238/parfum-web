<?php

use App\Enums\OrderStatus;
use App\Events\OrderPlaced;
use App\Exceptions\OrderException;
use App\Mail\OrderPlacedMail;
use App\Mail\OrderShippedMail;
use App\Mail\PaymentReceivedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Voucher;
use App\Services\OrderService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->orders = app(OrderService::class);
    $this->variant = ProductVariant::factory()->create(['stock' => 3]);
});

function orderWithItem(ProductVariant $variant, int $qty = 2, array $attrs = []): Order
{
    $order = Order::factory()->create($attrs);
    OrderItem::factory()->for($order)->create(['product_variant_id' => $variant->id, 'qty' => $qty]);

    return $order;
}

it('membatalkan order mengembalikan stok dan kuota voucher', function () {
    $voucher = Voucher::factory()->create(['used' => 1]);
    $order = orderWithItem($this->variant, 2, ['voucher_id' => $voucher->id]);

    $this->orders->cancel($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($this->variant->fresh()->stock)->toBe(5)
        ->and($voucher->fresh()->used)->toBe(0);
});

it('membatalkan dua kali tidak mengembalikan stok dua kali', function () {
    $order = orderWithItem($this->variant);

    $this->orders->cancel($order);
    expect(fn () => $this->orders->cancel($order->fresh()))->toThrow(OrderException::class);

    expect($this->variant->fresh()->stock)->toBe(5);
});

it('pembatalan tetap jalan walau varian sudah dihapus', function () {
    $order = orderWithItem($this->variant);
    $this->variant->delete();

    $this->orders->cancel($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});

it('order yang sudah dikirim tidak bisa dibatalkan', function () {
    $order = orderWithItem($this->variant, 1, ['status' => OrderStatus::Shipped]);

    expect(fn () => $this->orders->cancel($order))->toThrow(OrderException::class);
});

it('perintah orders:expire membatalkan order lewat 24 jam saja', function () {
    $overdue = orderWithItem($this->variant, 1, ['expires_at' => now()->subMinute()]);
    $fresh = orderWithItem($this->variant, 1, ['expires_at' => now()->addHour()]);
    $paid = orderWithItem($this->variant, 1, ['expires_at' => now()->subHour(), 'status' => OrderStatus::Paid]);

    $this->artisan('orders:expire')->assertSuccessful();

    expect($overdue->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($fresh->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and($paid->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($this->variant->fresh()->stock)->toBe(4);
});

it('orders:expire terjadwal di scheduler', function () {
    $events = collect(app(Schedule::class)->events())
        ->map(fn ($e) => $e->command);

    expect($events->contains(fn ($c) => str_contains((string) $c, 'orders:expire')))->toBeTrue()
        ->and($events->contains(fn ($c) => str_contains((string) $c, 'queue:work')))->toBeTrue();
});

it('markPaid mengubah status dan mengirim email pembayaran', function () {
    $order = orderWithItem($this->variant);

    $this->orders->markPaid($order, ['payment_type' => 'qris']);

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
    Mail::assertQueued(PaymentReceivedMail::class, fn ($m) => $m->order->is($order));
});

it('markPaid pada order yang sudah dibatalkan tidak mengubah status', function () {
    $order = orderWithItem($this->variant, 1, ['status' => OrderStatus::Cancelled]);

    expect(fn () => $this->orders->markPaid($order, []))->toThrow(OrderException::class);
});

it('alur dikemas, kirim dengan resi, lalu diterima', function () {
    $order = orderWithItem($this->variant, 1, ['status' => OrderStatus::Paid]);

    $this->orders->markProcessing($order);
    expect($order->fresh()->status)->toBe(OrderStatus::Processing);

    $this->orders->ship($order->fresh(), ' JNE123456789 ');
    expect($order->fresh()->status)->toBe(OrderStatus::Shipped)
        ->and($order->fresh()->tracking_number)->toBe('JNE123456789');
    Mail::assertQueued(OrderShippedMail::class);

    $this->orders->complete($order->fresh());
    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
});

it('tidak bisa kirim tanpa resi atau sebelum dibayar', function () {
    $pending = orderWithItem($this->variant, 1);
    $paid = orderWithItem($this->variant, 1, ['status' => OrderStatus::Paid]);

    expect(fn () => $this->orders->ship($pending, 'RESI1'))->toThrow(OrderException::class);
    expect(fn () => $this->orders->ship($paid, '  '))->toThrow(OrderException::class);
});

it('pesanan diterima hanya dari status dikirim', function () {
    $order = orderWithItem($this->variant, 1, ['status' => OrderStatus::Paid]);

    expect(fn () => $this->orders->complete($order))->toThrow(OrderException::class);
});

it('order baru mengirim email invoice lewat queue', function () {
    $order = orderWithItem($this->variant);

    OrderPlaced::dispatch($order);

    Mail::assertQueued(OrderPlacedMail::class, fn ($m) => $m->hasTo($order->user->email));
});
