<?php

namespace Database\Factories;

use App\Enums\CustomerGender;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->e164PhoneNumber(),
            'dob' => fake()->dateTimeBetween('-35 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(CustomerGender::getAllValues()),
            'email_verified_at' => now(),
            'password' => 'password',
            'remember_token' => Str::random(10),
        ];
    }
}
