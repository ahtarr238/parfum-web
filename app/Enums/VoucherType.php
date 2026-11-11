<?php

namespace App\Enums;

enum VoucherType: string
{
    case Fixed = 'fixed';
    case Percent = 'percent';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Potongan nominal',
            self::Percent => 'Potongan persen',
        };
    }
}
