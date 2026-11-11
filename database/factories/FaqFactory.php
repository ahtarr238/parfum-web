<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question' => fake()->sentence(4),
            'keywords' => implode(',', fake()->words(3)),
            'answer' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
