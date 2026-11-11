<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'midtrans_order_id' => 'MT-'.Str::upper(Str::random(12)),
            'status' => 'pending',
        ];
    }
}
