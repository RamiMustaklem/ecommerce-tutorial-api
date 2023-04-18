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
            'name' => $name = fake()->sentence,
            'slug' => Str::slug($name),
            'description' => fake()->paragraphs(4, true),
            'is_published' => fake()->boolean(),
            'quantity' => fake()->numberBetween(5, 50),
            'price' => $price = fake()->randomFloat(2, 45, 199),
            'old_price' => $price - ($price * 0.10),
            'images' => json_encode([[
                'id' => 1,
                'original' => fake()->imageUrl(640, 480, 'animals', true, 'dogs', false, 'jpg'),
                'thumbnail' => fake()->imageUrl(240, 180, 'animals', true, 'dogs', false, 'jpg'),
            ]]),
        ];
    }
}
