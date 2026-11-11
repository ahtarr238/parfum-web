<?php

if (! function_exists('rupiah')) {
    /**
     * Format angka integer Rupiah: 350000 -> "Rp350.000".
     */
    function rupiah(int|float|null $amount): string
    {
        return 'Rp'.number_format((int) $amount, 0, ',', '.');
    }
}

if (! function_exists('region_name')) {
    /**
     * Rapikan nama wilayah dari RajaOngkir (huruf kapital semua) tanpa merusak singkatan.
     * "DKI JAKARTA" -> "DKI Jakarta", "KOTA BANDUNG" -> "Kota Bandung".
     */
    function region_name(?string $name): string
    {
        $keep = ['DKI', 'DI', 'NTB', 'NTT', 'II', 'III'];

        return collect(preg_split('/(\s+)/', trim((string) $name), -1, PREG_SPLIT_DELIM_CAPTURE))
            ->map(fn (string $part) => in_array(strtoupper($part), $keep, true)
                ? strtoupper($part)
                : preg_replace_callback('/\p{L}+/u', fn ($m) => mb_convert_case($m[0], MB_CASE_TITLE), mb_strtolower($part)))
            ->implode('');
    }
}
