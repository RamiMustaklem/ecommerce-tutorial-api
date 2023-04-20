<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
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
            'description' => fake()->paragraph(1),
            'image' => json_encode([
                'id' => 1,
                'original' => fake()->imageUrl(640, 480, 'animals', true, 'dogs', false, 'jpg'),
                'thumbnail' => fake()->imageUrl(240, 180, 'animals', true, 'dogs', false, 'jpg'),
            ]),
        ];
    }
}
