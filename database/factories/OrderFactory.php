<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(100, 2000) * 1000;
        $shipping = fake()->numberBetween(9, 50) * 1000;

        return [
            'code' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),
            'user_id' => User::factory(),
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'discount' => 0,
            'total' => $subtotal + $shipping,
            'courier' => 'jne',
            'courier_service' => 'REG',
            'address_snapshot' => [
                'recipient' => fake()->name(),
                'phone' => '081234567890',
                'detail' => fake()->streetAddress(),
                'district_name' => 'Coblong',
                'city_name' => 'Kota Bandung',
                'province_name' => 'Jawa Barat',
                'postal_code' => '40132',
            ],
            'expires_at' => now()->addDay(),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
