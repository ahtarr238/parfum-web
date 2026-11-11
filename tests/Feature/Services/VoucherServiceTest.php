<?php

use App\Exceptions\VoucherException;
use App\Models\User;
use App\Models\Voucher;
use App\Services\VoucherService;

beforeEach(function () {
    $this->service = app(VoucherService::class);
    $this->user = User::factory()->create();
});

it('menghitung potongan nominal dan persen', function () {
    $fixed = Voucher::factory()->create(['value' => 20000]);
    $percent = Voucher::factory()->percent(15)->create();

    expect($this->service->discountFor($fixed, 300000))->toBe(20000)
        ->and($this->service->discountFor($fixed, 15000))->toBe(15000)
        ->and($this->service->discountFor($percent, 333333))->toBe(49999);
});

it('memvalidasi kode tanpa membedakan huruf besar kecil', function () {
    Voucher::factory()->create(['code' => 'HEMAT20']);

    expect($this->service->preview('hemat20', 100000)->code)->toBe('HEMAT20');
});

it('menolak voucher yang tidak ada, belum mulai, atau sudah lewat', function () {
    Voucher::factory()->create(['code' => 'NANTI', 'starts_at' => now()->addDay()]);
    Voucher::factory()->create(['code' => 'LEWAT', 'ends_at' => now()->subMinute()]);

    expect(fn () => $this->service->preview('ASAL', 100000))->toThrow(VoucherException::class, 'tidak ditemukan');
    expect(fn () => $this->service->preview('NANTI', 100000))->toThrow(VoucherException::class, 'belum berlaku');
    expect(fn () => $this->service->preview('LEWAT', 100000))->toThrow(VoucherException::class, 'sudah berakhir');
});

it('menolak jika minimal belanja belum tercapai', function () {
    Voucher::factory()->create(['code' => 'MIN', 'min_purchase' => 500000]);

    expect(fn () => $this->service->preview('MIN', 499999))
        ->toThrow(VoucherException::class, 'Minimal belanja Rp500.000');
});

it('claim menambah pemakaian dan menolak saat kuota habis', function () {
    $voucher = Voucher::factory()->create(['code' => 'DUA', 'quota' => 2]);

    $this->service->claim('DUA', 100000, $this->user);
    $this->service->claim('DUA', 100000, $this->user);

    expect($voucher->fresh()->used)->toBe(2);
    expect(fn () => $this->service->claim('DUA', 100000, $this->user))->toThrow(VoucherException::class, 'kuota');
});

it('release mengembalikan kuota tanpa jadi negatif', function () {
    $voucher = Voucher::factory()->create(['used' => 1]);

    $this->service->release($voucher);
    $this->service->release($voucher->fresh());

    expect($voucher->fresh()->used)->toBe(0);
});
