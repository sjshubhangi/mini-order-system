<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 50, 5000),
            'stock' => $this->faker->numberBetween(0, 50),
            'image_key' => null,
            'vendor_id' => 1, // override in tests
        ];
    }
}
