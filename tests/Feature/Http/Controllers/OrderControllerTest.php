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

        $this->user = User::factory()->create();
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
}
