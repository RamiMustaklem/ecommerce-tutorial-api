<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $name = fake()->unique()->catchPhrase(),
            'slug' => Str::slug($name),
            'description' => fake()->realText(),
            'is_published' => fake()->boolean(),
            'quantity' => fake()->numberBetween(5, 50),
            'price' => $price = fake()->randomFloat(2, 45, 199),
            'old_price' => $price + ($price * 0.10),
        ];
    }

    /**
     * Indicate that the product is published or not.
     */
    public function published($published = true): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => $published,
        ]);
    }
}
