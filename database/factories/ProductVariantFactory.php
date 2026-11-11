<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        $size = fake()->randomElement([30, 50, 100]);

        return [
            'product_id' => Product::factory(),
            'size_ml' => $size,
            'is_decant' => false,
            'sku' => 'SKU-'.Str::upper(Str::random(8)),
            'price' => fake()->numberBetween(50, 3000) * 1000,
            'stock' => fake()->numberBetween(1, 50),
            'weight_gram' => $size * 3 + 150,
        ];
    }

    public function decant(int $sizeMl = 10): static
    {
        return $this->state(['size_ml' => $sizeMl, 'is_decant' => true, 'weight_gram' => 50]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock' => 0]);
    }
}
