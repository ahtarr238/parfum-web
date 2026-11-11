<?php

namespace Database\Factories;

use App\Enums\ScentFamily;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->paragraph(),
            'scent_family' => fake()->randomElement(ScentFamily::cases()),
            'top_notes' => 'Bergamot, Lemon',
            'middle_notes' => 'Melati, Mawar',
            'base_notes' => 'Cedarwood, Musk',
            'images' => [],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
