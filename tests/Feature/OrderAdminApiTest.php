<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
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

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_index(): void
    {
        $orders = Order::factory()->count(5)->create();

        $this->assertCount(5, $orders);

        $response = $this->getJson($this->baseUrl);

        $order = $orders->first();

        $customer = DB::table('customers')->find($order->customer_id);

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
                            ->has(
                                'customer',
                                fn (AssertableJson $json) =>
                                $json->where('id', $customer->id)
                                    ->where('name', $customer->name)
                                    ->where('email', $customer->email)
                                    ->missing('password')
                                    ->etc()
                            )
                    )
                    ->etc()
            );

        $response->assertOk();
    }

    public function test_store_successfully(): void
    {
        $customer = Customer::factory()->create();

        $order = [
            'customer_id' => $customer->id,
            'total_price' => fake()->randomFloat(2, 100, 200),
        ];

        $response = $this->postJson($this->baseUrl, $order);

        $response->assertCreated();

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
                ...$order,
                'status' => OrderStatus::NEW->value
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
        $order = Order::factory()->create();

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
                        ->has('customer')
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
        $customer = Customer::factory()->create();

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
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'uuid' => $order->uuid,
            'status' => OrderStatus::PROCESSING->value,
            'notes' => 'notes updated',
            'customer_id' => $customer->id,
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
