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
