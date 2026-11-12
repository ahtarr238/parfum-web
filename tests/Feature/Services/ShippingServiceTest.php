<?php

use App\Exceptions\ShippingException;
use App\Services\ShippingService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.rajaongkir.key' => 'test-key',
        'services.rajaongkir.base_url' => 'https://rajaongkir.test/api/v1',
        'services.rajaongkir.origin' => 1391,
        'services.rajaongkir.couriers' => 'jne:sicepat',
        'services.rajaongkir.fake' => false,
    ]);
    Cache::flush();
});

it('mengambil daftar provinsi dan meng-cache-nya', function () {
    Http::fake([
        'rajaongkir.test/api/v1/destination/province' => Http::response(['meta' => ['code' => 200], 'data' => [
            ['id' => 9, 'name' => 'JAWA BARAT'],
            ['id' => 6, 'name' => 'DKI JAKARTA'],
        ]]),
    ]);
    $service = app(ShippingService::class);

    expect($service->provinces())->toBe([6 => 'DKI JAKARTA', 9 => 'JAWA BARAT']);
    $service->provinces();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $r) => $r->hasHeader('key', 'test-key'));
});

it('mengambil kota dan kecamatan berdasarkan induknya', function () {
    Http::fake([
        'rajaongkir.test/api/v1/destination/city/9' => Http::response(['data' => [['id' => 23, 'name' => 'BANDUNG']]]),
        'rajaongkir.test/api/v1/destination/district/23' => Http::response(['data' => [['id' => 1001, 'name' => 'COBLONG']]]),
    ]);
    $service = app(ShippingService::class);

    expect($service->cities(9))->toBe([23 => 'BANDUNG'])
        ->and($service->districts(23))->toBe([1001 => 'COBLONG']);
});

it('menghitung ongkir dari total berat dan meng-cache per tujuan dan berat', function () {
    Http::fake([
        'rajaongkir.test/api/v1/calculate/district/domestic-cost' => Http::response(['data' => [
            ['name' => 'Jalur Nugraha Ekakurir (JNE)', 'code' => 'jne', 'service' => 'REG', 'description' => 'Layanan Reguler', 'cost' => 18000, 'etd' => '2-3 day'],
            ['name' => 'SiCepat Express', 'code' => 'sicepat', 'service' => 'BEST', 'description' => 'Besok Sampai Tujuan', 'cost' => 25000, 'etd' => '1 day'],
        ]]),
    ]);
    $service = app(ShippingService::class);

    $options = $service->costs(1001, 750);
    $service->costs(1001, 750);
    $service->costs(1001, 1200);

    expect($options)->toHaveCount(2)
        ->and($options[0]->courier)->toBe('jne')
        ->and($options[0]->service)->toBe('REG')
        ->and($options[0]->cost)->toBe(18000)
        ->and($options[0]->key())->toBe('jne|REG')
        ->and($options[1]->etd)->toBe('1 hari');

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'domestic-cost')
        && $r['origin'] == 1391 && $r['destination'] == 1001 && $r['weight'] == 750 && $r['courier'] === 'jne:sicepat');
});

it('mengurutkan ongkir dari termurah dan membuang layanan biaya nol', function () {
    Http::fake(['*' => Http::response(['data' => [
        ['name' => 'B', 'code' => 'jne', 'service' => 'YES', 'cost' => 40000, 'etd' => '1'],
        ['name' => 'A', 'code' => 'jne', 'service' => 'REG', 'cost' => 18000, 'etd' => '2-3'],
        ['name' => 'C', 'code' => 'pos', 'service' => 'X', 'cost' => 0, 'etd' => ''],
    ]])]);

    $options = app(ShippingService::class)->costs(1001, 500);

    expect(array_map(fn ($o) => $o->service, $options))->toBe(['REG', 'YES']);
});

it('melempar ShippingException saat API gagal', function () {
    Http::fake(['*' => Http::response(['meta' => ['message' => 'Invalid key']], 401)]);

    expect(fn () => app(ShippingService::class)->costs(1001, 500))
        ->toThrow(ShippingException::class, 'Ongkir belum bisa dihitung');
});

it('tidak meng-cache kegagalan', function () {
    // retry(2): dua percobaan gagal dulu, lalu panggilan berikutnya sukses.
    Http::fakeSequence()
        ->push(['meta' => []], 500)
        ->push(['meta' => []], 500)
        ->push(['data' => [['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'cost' => 9000, 'etd' => '2']]]);
    $service = app(ShippingService::class);

    expect(fn () => $service->costs(1001, 500))->toThrow(ShippingException::class);
    expect($service->costs(1001, 500))->toHaveCount(1);
});

it('menemukan opsi berdasarkan key', function () {
    Http::fake(['*' => Http::response(['data' => [
        ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'cost' => 18000, 'etd' => '2-3'],
    ]])]);

    $service = app(ShippingService::class);

    expect($service->findOption(1001, 500, 'jne|REG')?->cost)->toBe(18000)
        ->and($service->findOption(1001, 500, 'jne|YES'))->toBeNull();
});

it('menyembunyikan layanan yang tidak relevan (trucking, motor, kargo)', function () {
    config(['services.rajaongkir.exclude_services' => ['JTR', 'T15', 'GOKIL', 'POS KARGO']]);
    Http::fake(['*' => Http::response(['data' => [
        ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Layanan Reguler', 'cost' => 13000, 'etd' => '1 day'],
        ['name' => 'JNE', 'code' => 'jne', 'service' => 'JTR', 'description' => 'JNE Trucking', 'cost' => 45000, 'etd' => '3 day'],
        ['name' => 'TIKI', 'code' => 'tiki', 'service' => 'T15', 'description' => 'Motor', 'cost' => 200000, 'etd' => '6 day'],
        ['name' => 'SiCepat', 'code' => 'sicepat', 'service' => 'GOKIL', 'description' => 'Kargo', 'cost' => 18300, 'etd' => '3-5 day'],
        ['name' => 'POS', 'code' => 'pos', 'service' => 'POS KARGO', 'description' => 'Pjb', 'cost' => 39500, 'etd' => '7-14 day'],
    ]])]);

    $options = app(ShippingService::class)->costs(1322, 900);

    expect(array_map(fn ($o) => $o->service, $options))->toBe(['REG']);
});

it('memakai nama layanan saat deskripsi kurir hanya angka dan menerjemahkan estimasi', function () {
    Http::fake(['*' => Http::response(['data' => [
        ['name' => 'POS', 'code' => 'pos', 'service' => 'Pos Reguler', 'description' => '240', 'cost' => 9900, 'etd' => '2 day'],
        ['name' => 'TIKI', 'code' => 'tiki', 'service' => 'SDS', 'description' => 'Same Day Service', 'cost' => 30000, 'etd' => '0 day'],
        ['name' => 'J&T', 'code' => 'jnt', 'service' => 'EZ', 'description' => 'Reguler', 'cost' => 11000, 'etd' => ''],
    ]])]);

    [$pos, $jnt, $tiki] = app(ShippingService::class)->costs(1322, 900);

    expect($pos->description)->toBe('Pos Reguler')
        ->and($pos->etd)->toBe('2 hari')
        ->and($tiki->etd)->toBe('hari ini')
        ->and($jnt->etd)->toBeNull();
});

it('mengganti asal gudang tidak memakai ongkir lama dari cache', function () {
    Http::fake(['*' => Http::response(['data' => [
        ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 13000, 'etd' => '1 day'],
    ]])]);
    $service = app(ShippingService::class);

    $service->costs(1322, 900);
    config(['services.rajaongkir.origin' => 9999]);
    $service->costs(1322, 900);

    Http::assertSentCount(2);
});
