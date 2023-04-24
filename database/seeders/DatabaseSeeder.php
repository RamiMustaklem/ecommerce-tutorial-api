<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $products = \App\Models\Product::factory(20)->create();
        $categories = \App\Models\Category::factory(5)->create();

        $products->each(function ($product) use ($categories) {
            $randCategories = $categories->random(rand(0, 3))->pluck('id');
            $product->categories()->attach($randCategories);
        });

        $customers = \App\Models\Customer::factory(50)->create();

        \App\Models\Order::factory(150)
            ->state(fn () => ['customer_id' => $customers->random()->id])
            ->create();
    }
}
