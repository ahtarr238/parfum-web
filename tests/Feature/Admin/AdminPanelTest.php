<?php

use App\Enums\OrderStatus;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\ReviewResource\Pages\ManageReviews;
use App\Filament\Resources\VoucherResource\Pages\ManageVouchers;
use App\Filament\Widgets\LowStockTable;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\StoreStats;
use App\Filament\Widgets\TopProductsTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use App\Models\Voucher;
use App\Support\StoreMetrics;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('dashboard admin bisa dibuka', function () {
    $this->get('/admin')->assertOk()->assertSeeLivewire(StoreStats::class);
});

it('omzet hanya menghitung order yang sudah dibayar', function () {
    $this->travelTo(now()->setDate(2026, 11, 15)->setTime(10, 0));
    Order::factory()->status(OrderStatus::Paid)->create(['total' => 300000]);
    Order::factory()->status(OrderStatus::Completed)->create(['total' => 200000]);
    Order::factory()->status(OrderStatus::Cancelled)->create(['total' => 999000]);
    Order::factory()->create(['total' => 888000]);
    Order::factory()->status(OrderStatus::Completed)->create(['total' => 50000, 'created_at' => now()->subMonth()]);

    $metrics = app(StoreMetrics::class);

    expect($metrics->revenueBetween(now()->startOfMonth(), now()))->toBe(500000)
        ->and($metrics->paidOrderCountBetween(now()->startOfMonth(), now()))->toBe(2);

    Livewire::test(StoreStats::class)->assertSee('Rp500.000');
});

it('grafik omzet berisi 30 hari', function () {
    Order::factory()->status(OrderStatus::Paid)->create(['total' => 100000]);

    $series = app(StoreMetrics::class)->dailyRevenue(30);

    expect($series)->toHaveCount(30)
        ->and(end($series))->toBe(100000);

    Livewire::test(RevenueChart::class)->assertOk();
});

it('produk terlaris dan stok menipis tampil di dashboard', function () {
    $low = ProductVariant::factory()->create(['stock' => 2]);
    ProductVariant::factory()->create(['stock' => 40]);
    $order = Order::factory()->status(OrderStatus::Completed)->create();
    OrderItem::factory()->for($order)->create(['product_variant_id' => $low->id, 'qty' => 4]);

    Livewire::test(LowStockTable::class)->assertCanSeeTableRecords([$low]);
    Livewire::test(TopProductsTable::class)->assertSee($low->product->name);
});

it('admin membuat voucher dan kode disimpan huruf besar', function () {
    Livewire::test(ManageVouchers::class)
        ->callAction('create', data: [
            'code' => 'wangi10',
            'type' => 'percent',
            'value' => 10,
            'min_purchase' => 200000,
            'quota' => 50,
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addMonth()->toDateTimeString(),
        ])
        ->assertHasNoActionErrors();

    expect(Voucher::first()->code)->toBe('WANGI10');
});

it('voucher persen tidak boleh lebih dari 100 dan tanggal akhir setelah mulai', function () {
    Livewire::test(ManageVouchers::class)
        ->callAction('create', data: [
            'code' => 'SALAH',
            'type' => 'percent',
            'value' => 150,
            'min_purchase' => 0,
            'quota' => 1,
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->subDay()->toDateTimeString(),
        ])
        ->assertHasActionErrors(['value', 'ends_at']);
});

it('admin menyembunyikan ulasan', function () {
    $review = Review::factory()->create(['is_visible' => true]);

    Livewire::test(ManageReviews::class)
        ->assertCanSeeTableRecords([$review])
        ->callTableAction('toggleVisibility', $review);

    expect($review->fresh()->is_visible)->toBeFalse();
});

it('daftar customer tidak memuat admin', function () {
    $customer = User::factory()->create();

    Livewire::test(ListCustomers::class)
        ->assertCanSeeTableRecords([$customer])
        ->assertCanNotSeeTableRecords([$this->admin]);

    $this->get(CustomerResource::getUrl('view', ['record' => $customer]))->assertOk();
});
