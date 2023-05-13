<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $customers = \App\Models\User::factory(20)->create();

        $products = \App\Models\Product::factory(20)->create();
        $categories = \App\Models\Category::factory(5)->create();

        $products->each(function ($product) use ($categories) {
            $randCategories = $categories->random(rand(0, 3))->pluck('id');
            $product->categories()->attach($randCategories);
        });

        $orders = \App\Models\Order::factory(150)
            ->state(fn () => [
                'customer_id' => $customers->random()->id,
            ])
            ->create();

        $orders->each(function ($order) use ($products) {

            $orderProducts = $products->where('quantity', '>', 0)
                ->random(rand(1, 3))
                ->mapWithKeys(function (Product $item) {

                    $itemQuantity = $item->quantity > 5
                        ? rand(1, 3)
                        : rand(1, $item->quantity);

                    return [
                        $item->id => [
                            'quantity' => $itemQuantity,
                            'unit_price' => $item->price,
                        ]
                    ];
                });

            $order->products()->attach($orderProducts->toArray());

            $orderTotal = $orderProducts->reduce(function (int $carry, array $value) {
                return $carry + ($value['quantity'] * $value['unit_price']);
            }, 0);

            $order->update(['total_price' => $orderTotal]);
        });

        \App\Models\User::factory()->admin()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
