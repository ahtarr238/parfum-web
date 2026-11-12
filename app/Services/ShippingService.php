<?php

namespace App\Services;

use App\Exceptions\ShippingException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Satu-satunya pintu ke API RajaOngkir (Komerce). Controller/Livewire/Filament tidak memanggil API langsung.
 */
class ShippingService
{
    private const REGION_TTL = 60 * 60 * 24 * 30;

    private const COST_TTL = 60 * 60 * 6;

    /** @return array<int, string> */
    public function provinces(): array
    {
        return $this->regions('provinces', 'destination/province');
    }

    /** @return array<int, string> */
    public function cities(int $provinceId): array
    {
        return $this->regions("cities.{$provinceId}", "destination/city/{$provinceId}");
    }

    /** @return array<int, string> */
    public function districts(int $cityId): array
    {
        return $this->regions("districts.{$cityId}", "destination/district/{$cityId}");
    }

    /**
     * Opsi kurir untuk tujuan dan berat tertentu, urut dari termurah.
     *
     * @return list<ShippingOption>
     */
    public function costs(int $destinationDistrictId, int $weightGram): array
    {
        $weight = max(1, $weightGram);
        // Kunci cache memuat asal, kurir, dan layanan yang disembunyikan supaya perubahan konfigurasi langsung berlaku.
        $key = "rajaongkir.cost.{$destinationDistrictId}.{$weight}.".md5(json_encode([
            (int) config('services.rajaongkir.origin'),
            (string) config('services.rajaongkir.couriers'),
            (array) config('services.rajaongkir.exclude_services'),
        ]));

        $rows = Cache::get($key);

        if ($rows === null) {
            $rows = $this->fetchCosts($destinationDistrictId, $weight);
            Cache::put($key, $rows, self::COST_TTL);
        }

        return array_map(fn (array $row) => ShippingOption::fromArray($row), $rows);
    }

    public function findOption(int $destinationDistrictId, int $weightGram, string $key): ?ShippingOption
    {
        foreach ($this->costs($destinationDistrictId, $weightGram) as $option) {
            if ($option->key() === $key) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchCosts(int $destination, int $weight): array
    {
        $data = $this->call(fn (PendingRequest $http) => $http->asForm()->post('calculate/district/domestic-cost', [
            'origin' => (int) config('services.rajaongkir.origin'),
            'destination' => $destination,
            'weight' => $weight,
            'courier' => (string) config('services.rajaongkir.couriers'),
            'price' => 'lowest',
        ]));

        $excluded = array_map('strtoupper', (array) config('services.rajaongkir.exclude_services', []));

        $options = collect($data)
            ->filter(fn ($row) => is_array($row) && (int) ($row['cost'] ?? 0) > 0)
            // Trucking, kirim motor, kargo, dsb. tidak relevan untuk paket parfum.
            ->reject(fn (array $row) => in_array(strtoupper(trim((string) ($row['service'] ?? ''))), $excluded, true))
            ->map(function (array $row) {
                $service = trim((string) ($row['service'] ?? ''));
                $description = trim((string) ($row['description'] ?? ''));

                return (new ShippingOption(
                    courier: strtolower((string) ($row['code'] ?? '')),
                    courierName: (string) ($row['name'] ?? ''),
                    service: $service,
                    // POS kadang mengirim kode angka ("240") sebagai deskripsi.
                    description: $description === '' || ctype_digit($description) ? $service : $description,
                    cost: (int) $row['cost'],
                    etd: $this->formatEtd((string) ($row['etd'] ?? '')),
                ))->toArray();
            })
            ->sortBy('cost')
            ->values()
            ->all();

        return $options;
    }

    /**
     * @return array<int, string>
     */
    private function regions(string $cacheKey, string $path): array
    {
        $key = "rajaongkir.{$cacheKey}";
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $regions = collect($this->call(fn (PendingRequest $http) => $http->get($path)))
            ->filter(fn ($row) => is_array($row) && isset($row['id'], $row['name']))
            ->mapWithKeys(fn (array $row) => [(int) $row['id'] => (string) $row['name']])
            ->sort()
            ->all();

        if ($regions) {
            Cache::put($key, $regions, self::REGION_TTL);
        }

        return $regions;
    }

    /**
     * @param  callable(PendingRequest): Response  $request
     * @return array<int|string, mixed>
     */
    private function call(callable $request): array
    {
        try {
            $response = $request(
                Http::baseUrl(rtrim((string) config('services.rajaongkir.base_url'), '/'))
                    ->withHeaders(['key' => (string) config('services.rajaongkir.key')])
                    ->acceptJson()
                    ->timeout(10)
                    ->retry(2, 300, throw: false)
            );
        } catch (Throwable $e) {
            Log::warning('RajaOngkir tidak bisa dihubungi', ['error' => $e->getMessage()]);
            throw new ShippingException('Ongkir belum bisa dihitung. Coba lagi sebentar.', previous: $e);
        }

        if (! $response->successful() || ! is_array($response->json('data'))) {
            Log::warning('RajaOngkir mengembalikan error', ['status' => $response->status(), 'body' => $response->json('meta')]);
            throw new ShippingException('Ongkir belum bisa dihitung. Coba lagi sebentar.');
        }

        return $response->json('data');
    }

    private function formatEtd(string $etd): ?string
    {
        $clean = trim(preg_replace('/\s*(days?|hari)\s*/i', '', $etd));

        return match ($clean) {
            '' => null,
            '0' => 'hari ini',
            default => "{$clean} hari",
        };
    }
}
