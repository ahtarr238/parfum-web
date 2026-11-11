<?php

namespace App\Support;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Data contoh RajaOngkir untuk development tanpa API key (RAJAONGKIR_FAKE=true, APP_ENV=local).
 * Bentuk respons mengikuti API Komerce supaya ShippingService tetap memakai jalur yang sama.
 */
class FakeRajaOngkir
{
    public const PROVINCES = [
        6 => 'DKI JAKARTA',
        9 => 'JAWA BARAT',
        10 => 'JAWA TENGAH',
        11 => 'JAWA TIMUR',
        1 => 'BALI',
    ];

    public const CITIES = [
        6 => [151 => 'JAKARTA SELATAN', 152 => 'JAKARTA PUSAT'],
        9 => [23 => 'BANDUNG', 115 => 'DEPOK'],
        10 => [398 => 'SEMARANG', 445 => 'SURAKARTA (SOLO)'],
        11 => [444 => 'SURABAYA', 255 => 'MALANG'],
        1 => [114 => 'DENPASAR'],
    ];

    public const DISTRICTS = [
        151 => [2101 => 'KEBAYORAN BARU', 2102 => 'TEBET'],
        152 => [2111 => 'MENTENG', 2112 => 'GAMBIR'],
        23 => [1001 => 'COBLONG', 1002 => 'SUKAJADI'],
        115 => [1101 => 'BEJI', 1102 => 'PANCORAN MAS'],
        398 => [3301 => 'SEMARANG TENGAH', 3302 => 'BANYUMANIK'],
        445 => [3401 => 'LAWEYAN', 3402 => 'BANJARSARI'],
        444 => [3501 => 'GUBENG', 3502 => 'WONOKROMO'],
        255 => [3601 => 'KLOJEN', 3602 => 'LOWOKWARU'],
        114 => [5101 => 'DENPASAR SELATAN', 5102 => 'DENPASAR UTARA'],
    ];

    public static function register(string $baseUrl): void
    {
        $base = rtrim($baseUrl, '/');

        Http::fake([
            "{$base}/*" => function (Request $request) use ($base) {
                $path = trim(substr(strtok($request->url(), '?'), strlen($base)), '/');

                return Http::response(['meta' => ['code' => 200, 'status' => 'success'], 'data' => self::data($path, $request)]);
            },
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function data(string $path, Request $request): array
    {
        $rows = fn (array $items) => collect($items)->map(fn ($name, $id) => ['id' => $id, 'name' => $name])->values()->all();

        return match (true) {
            $path === 'destination/province' => $rows(self::PROVINCES),
            str_starts_with($path, 'destination/city/') => $rows(self::CITIES[(int) substr($path, 17)] ?? []),
            str_starts_with($path, 'destination/district/') => $rows(self::DISTRICTS[(int) substr($path, 21)] ?? []),
            $path === 'calculate/district/domestic-cost' => self::costs((int) $request['destination'], (int) $request['weight']),
            default => [],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function costs(int $destination, int $weight): array
    {
        $kg = max(1, (int) ceil($weight / 1000));
        $zone = $destination >= 5000 ? 3 : ($destination >= 3000 ? 2 : 1);

        return [
            ['name' => 'Jalur Nugraha Ekakurir (JNE)', 'code' => 'jne', 'service' => 'REG', 'description' => 'Layanan Reguler', 'cost' => (8000 + 4000 * $zone) * $kg, 'etd' => (1 + $zone).'-'.(2 + $zone).' day'],
            ['name' => 'Jalur Nugraha Ekakurir (JNE)', 'code' => 'jne', 'service' => 'YES', 'description' => 'Yakin Esok Sampai', 'cost' => (16000 + 6000 * $zone) * $kg, 'etd' => '1 day'],
            ['name' => 'J&T Express', 'code' => 'jnt', 'service' => 'EZ', 'description' => 'Reguler', 'cost' => (8500 + 3500 * $zone) * $kg, 'etd' => (2 + $zone).' day'],
            ['name' => 'SiCepat Express', 'code' => 'sicepat', 'service' => 'REG', 'description' => 'Reguler', 'cost' => (7500 + 4000 * $zone) * $kg, 'etd' => (1 + $zone).'-'.(2 + $zone).' day'],
        ];
    }
}
