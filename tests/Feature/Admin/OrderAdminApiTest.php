<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class OrderAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private $baseUrl = '/api/admin/orders';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->admin()->create();
        $this->actingAs($user);
    }

    public function test_index(): void
    {
        $products = Product::factory()->count(rand(3, 5))->create();

        $orders = Order::factory()
            ->count(5)
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

        $this->assertCount(5, $orders);

        $response = $this->getJson($this->baseUrl);

        $order = $orders->first();

        $customer = DB::table('users')->find($order->customer_id);

        $order_products_pivot = DB::table('order_product')
            ->where('order_id', $order->id)
            ->get();

        $response
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('data', 5)
                    ->has('meta')
                    ->has('meta.current_page')
                    ->has('meta.total')
                    ->where('meta.current_page', 1)
                    ->where('meta.per_page', 15)
                    ->where('meta.last_page', 1)
                    ->where('meta.total', 5)
                    ->has(
                        'data.0',
                        fn (AssertableJson $json) =>
                        $json->where('id', $order->id)
                            ->where('uuid', $order->uuid)
                            ->where('customer_id', $order->customer_id)
                            ->where('total_price', $order->total_price)
                            ->where('status', $order->status->value)
                            ->where('notes', $order->notes)
                            ->where('address', $order->address)
                            ->has(
                                'customer',
                                fn (AssertableJson $json) =>
                                $json->where('id', $customer->id)
                                    ->where('name', $customer->name)
                                    ->where('email', $customer->email)
                                    ->missing('password')
                                    ->etc()
                            )
                            ->has('products', count($order_products_pivot))
                            ->where(
                                'total_price',
                                number_format(
                                    $order_products_pivot->reduce(function (int $carry, $value) {
                                        return $carry + ($value->quantity * $value->unit_price);
                                    }, 0),
                                    2
                                )
                            )
                    )
                    ->etc()
            );

        $response->assertOk();
    }

    public function test_store_successfully(): void
    {
        $customer = User::factory()->create();

        $products = Product::factory()->count(2)->create();

        $order_products = $products->map(
            fn ($product) => ['quantity' => 1, 'product_id' => $product->id]
        )->toArray();

        $address = [
            'city' => fake()->city,
            'street_address' => fake()->streetAddress,
        ];

        $order = [
            'customer_id' => $customer->id,
            'total_price' => fake()->randomFloat(2, 100, 200),
            'address' => $address,
        ];

        $response = $this->postJson($this->baseUrl, [
            ...$order,
            'order_products' => $order_products,
        ]);

        $response->assertCreated();

        $created = $response->json('data');
        $uuid = $created['uuid'];
        $total_price = $created['total_price'];

        $response->assertJson(
            fn (AssertableJson $json) => $json->has(
                'data',
                fn (AssertableJson $json) =>
                $json->has('uuid')
                    ->where('status', OrderStatus::NEW->value)
                    ->where('customer_id', $customer->id)
                    ->missing('customer')
                    ->etc()
            )
        );

        $this->assertDatabaseHas(
            'orders',
            [
                'customer_id' => $order['customer_id'],
                'total_price' => $order['total_price'],
                'status' => OrderStatus::NEW->value,
                'uuid' => $uuid,
                'total_price' => $total_price,
            ]
        );
    }

    public function test_store_validation_errors(): void
    {
        $response = $this->postJson($this->baseUrl, [
            'total_price' => fake()->randomFloat(2, 100, 200),
        ]);

        $response->assertInvalid([
            'customer_id' => 'The customer id field is required.',
        ]);

        $response->assertUnprocessable();
    }

    public function test_show(): void
    {
        $products = Product::factory()->count(2)->create(['quantity' => 10]);

        $order = Order::factory()->create();

        $orderProducts = $products->mapWithKeys(function (Product $item) {
            return [
                $item->id => [
                    'quantity' => rand(1, 3),
                    'unit_price' => $item->price,
                ]
            ];
        });

        $order->products()->attach($orderProducts->toArray());

        $orderTotal = $orderProducts->reduce(function (int $carry, array $value) {
            return $carry + ($value['quantity'] * $value['unit_price']);
        }, 0);

        $order->update(['total_price' => $orderTotal]);

        $response = $this->getJson($this->baseUrl . '/' . $order->id);

        $response
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    fn (AssertableJson $json) =>
                    $json->where('id', $order->id)
                        ->where('status', $order->status->value)
                        ->where('customer_id', $order->customer_id)
                        ->where('uuid', $order->uuid)
                        ->where('total_price', $order->total_price)
                        ->has('customer')
                        ->has('products', $products->count())
                        ->where('total_price', number_format($orderTotal, 2))
                        ->etc()
                )
            );

        $response->assertOk();
    }

    public function test_show_404(): void
    {
        $this->assertDatabaseEmpty('orders');

        $response = $this->getJson($this->baseUrl . '/' . 100);

        $response->assertNotFound();
    }

    public function test_update_successfully(): void
    {
        $customer = User::factory()->create();

        $products = Product::factory()->count(2)->create();

        $order_products = $products->map(
            fn ($product) => ['quantity' => 1, 'product_id' => $product->id]
        )->toArray();

        $order = Order::factory()->create([
            'status' => OrderStatus::NEW->value,
            'customer_id' => $customer->id,
            'notes' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'uuid' => $order->uuid,
            'status' => OrderStatus::NEW->value,
            'notes' => null,
            'customer_id' => $customer->id,
        ]);

        $response = $this->putJson($this->baseUrl . '/' . $order->id, [
            'notes' => 'notes updated',
            'status' => OrderStatus::PROCESSING->value,
            'order_products' => $order_products,
        ]);

        $created = $response->json('data');
        $total_price = $created['total_price'];

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'uuid' => $order->uuid,
            'status' => OrderStatus::PROCESSING->value,
            'notes' => 'notes updated',
            'customer_id' => $customer->id,
            'total_price' => $total_price,
        ]);

        $response
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    fn (AssertableJson $json) =>
                    $json->where('id', $order->id)
                        ->where('customer_id', $order->customer_id)
                        ->where('uuid', $order->uuid)
                        ->where('notes', 'notes updated')
                        ->where('status', OrderStatus::PROCESSING->value)
                        ->missing('customer')
                        ->etc()
                )
            );

        $response->assertOk();
    }

    public function test_update_validation_errors(): void
    {
        $order = Order::factory()->create();

        $response = $this->putJson($this->baseUrl . '/' . $order->id, [
            'customer_id' => null,
        ]);

        $response->assertInvalid([
            'status' => 'The status field is required.',
        ]);

        $response->assertUnprocessable();
    }

    public function test_destroy(): void
    {
        $order = Order::factory()->create();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'uuid' => $order->uuid,
        ]);

        $response = $this->deleteJson($this->baseUrl . '/' . $order->id);

        $response->assertSuccessful();

        $this->assertSoftDeleted('orders', [
            'id' => $order->id,
            'uuid' => $order->uuid,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'uuid' => $order->uuid,
        ]);
        $this->assertModelExists($order);
    }

    public function test_destroy_404(): void
    {
        $this->assertDatabaseEmpty('orders');

        $response = $this->deleteJson($this->baseUrl . '/' . 100);

        $response->assertNotFound();
    }

    public function test_restore(): void
    {
        $order = Order::factory()->create();
        $order->delete();

        $data = [
            'id' => $order->id,
            'uuid' => $order->uuid,
        ];

        $this->assertModelExists($order);
        $this->assertDatabaseHas('orders', $data);
        $this->assertSoftDeleted('orders', $data);

        $response = $this->putJson($this->baseUrl . '/' . $order->id . '/restore');

        $response->assertSuccessful();

        $this->assertNotSoftDeleted('orders', $data);
    }

    public function test_restore_404(): void
    {
        $this->assertDatabaseEmpty('orders');

        $response = $this->putJson($this->baseUrl . '/' . 100 . '/restore');

        $response->assertNotFound();
    }
}
