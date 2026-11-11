<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(['Rumah', 'Kantor', 'Kos']),
            'recipient' => fake()->name(),
            'phone' => '08'.fake()->numerify('##########'),
            'province_id' => 9,
            'province_name' => 'Jawa Barat',
            'city_id' => 23,
            'city_name' => 'Kota Bandung',
            'district_id' => 1001,
            'district_name' => 'Coblong',
            'detail' => fake()->streetAddress(),
            'postal_code' => '40132',
            'is_default' => false,
        ];
    }
}
