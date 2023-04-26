<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'uuid' => Str::orderedUuid(),
            'total_price' => fake()->randomFloat(2, 50, 500),
            'status' => fake()->randomElement(OrderStatus::getAllValues()),
            'notes' => fake()->realText(100),
            'address' => json_encode([
                'street_address' => fake()->streetAddress,
                'city' => fake()->city,
            ]),
        ];
    }
}
