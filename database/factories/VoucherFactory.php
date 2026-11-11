<?php

namespace Database\Factories;

use App\Enums\VoucherType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VoucherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'type' => VoucherType::Fixed,
            'value' => 20000,
            'min_purchase' => 0,
            'quota' => 100,
            'used' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ];
    }

    public function percent(int $value = 10): static
    {
        return $this->state(['type' => VoucherType::Percent, 'value' => $value]);
    }
}
