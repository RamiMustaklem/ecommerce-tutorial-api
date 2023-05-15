<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private $baseUrl = '/api/orders';
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->customer()->create();
        $this->actingAs($this->user);
    }

    public function test_get_active_orders_list_for_logged_in_user_only(): void
    {
        $customers = User::factory(3)->create()->push($this->user);

        $orders = Order::factory(20)
            ->state(fn () => [
                'customer_id' => $customers->random()->id,
            ])
            ->create();

        $products = Product::factory(50)->published()->state(['quantity' => 50])->create();

        $orders->each(function ($order) use ($products) {
            $orderProducts = $products->random(5)
                ->mapWithKeys(fn (Product $item) => [
                    $item->id => [
                        'quantity' => 1,
                        'unit_price' => $item->price,
                    ]
                ]);

            $order->products()->attach($orderProducts->toArray());
        });

        $active_customer_orders = $orders
            ->filter(
                fn ($order) =>
                in_array($order->status->value, OrderStatus::getActiveValues()) &&
                    $order->customer_id === $this->user->id
            );

        $this->assertAuthenticated();

        $response = $this->getJson($this->baseUrl);

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('data', $active_customer_orders->count())
                    ->has(
                        'data',
                        fn (AssertableJson $json) =>
                        $json->each(
                            fn (AssertableJson $json) =>
                            $json
                                ->has('id')
                                ->where('customer_id', $this->user->id)
                                ->has('uuid')
                                ->has('total_price')
                                ->whereNot('status', OrderStatus::CANCELLED->value)
                                ->whereNot('status', OrderStatus::DELIVERED->value)
                                ->has('address')
                                ->has(
                                    'products',
                                    fn (AssertableJson $json) =>
                                    $json->each(
                                        fn (AssertableJson $json) =>
                                        $json->has('order_product')
                                            ->has('name')
                                            ->has('slug')
                                            ->etc()
                                    )
                                )
                                ->missing('customer')
                                ->etc()
                        )
                    )
                    ->where('meta.current_page', 1)
                    ->where('meta.total', $active_customer_orders->count())
                    ->etc()
            );
    }

    public function test_successfully_get_single_order_by_uuid_for_logged_in_user_only(): void
    {
        $order = Order::factory()
            ->state(['customer_id' => $this->user->id])
            ->create();

        $published_products = Product::factory(5)->state(['is_published' => true])->create();
        $unpublished_products = Product::factory(5)->state(['is_published' => false])->create();
        $products = $published_products->merge($unpublished_products);
        $published_count = $published_products->count();

        $order->products()
            ->attach(
                $products
                    ->mapWithKeys(
                        fn ($product) => [
                            $product->id => [
                                'quantity' => 1,
                                'unit_price' => $product->price,
                            ],
                        ]
                    )
                    ->toArray()
            );

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('products', 10);

        $this->assertAuthenticated();

        $response = $this->getJson($this->baseUrl . '/' . $order->uuid);

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    fn (AssertableJson $json) =>
                    $json->has('uuid')
                        ->has('customer_id')
                        ->has('total_price')
                        ->has('status')
                        ->has('notes')
                        ->has('address')
                        ->has('products', $published_count)
                        ->has(
                            'products',
                            fn (AssertableJson $json) =>
                            $json->each(
                                fn (AssertableJson $json) =>
                                $json->has('slug')
                                    ->has('name')
                                    ->has('order_product.quantity')
                                    ->has('order_product.unit_price')
                            )
                        )
                        ->etc()
                )
            );
    }

    public function test_404_get_single_order_by_uuid_for_different_user(): void
    {
        $customer = User::factory()->create();

        $order = Order::factory()
            ->state(fn () => ['customer_id' => $customer->id])
            ->create();

        $this->assertAuthenticated();

        $response = $this->getJson($this->baseUrl . '/' . $order->id);

        $response->assertNotFound();
    }

    public function test_create_order_on_checkout_successfully_for_logged_in_user(): void
    {
        $products = Product::factory(rand(2, 5))->published()->create();

        $order_products = $products->map(fn ($product) => [
            'product_id' => $product->id,
            'quantity' => rand(1, $product->quantity > 5 ? 5 : $product->quantity),
        ]);

        $pivot = $products->mapWithKeys(function ($product) use ($order_products) {
            $quantity = $order_products->firstWhere('product_id', $product->id)['quantity'];
            $unit_price = $product->price;
            return [$product->id => compact('quantity', 'unit_price')];
        });

        $total_price = $pivot->reduce(
            fn (?int $carry, $item) => $carry + ($item['quantity'] * $item['unit_price']),
            0
        );

        $address = [
            'street_address' => fake()->streetAddress,
            'city' => fake()->city,
        ];

        $this->assertAuthenticated();

        $response = $this->postJson($this->baseUrl, [
            'address' => $address,
            'order_products' => $order_products,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->user->id,
            'total_price' => $total_price,
            'status' => OrderStatus::NEW->value,
        ]);
    }

    public function test_create_order_on_checkout_fails_validation_for_logged_in_user(): void
    {
        $address = [
            'street_address' => fake()->streetAddress,
            'city' => fake()->city,
        ];

        $this->assertAuthenticated();

        $response = $this->postJson($this->baseUrl, [
            'address' => $address,
            'order_products' => [
                ['product_id' => 100, 'quantity' => 0],
            ],
        ]);

        $response->assertInvalid([
            'order_products.0.product_id' => 'The selected order_products.0.product_id is invalid.',
            'order_products.0.quantity' => 'The order_products.0.quantity field must be at least 1.',
        ]);

        $response->assertUnprocessable();
    }
}
