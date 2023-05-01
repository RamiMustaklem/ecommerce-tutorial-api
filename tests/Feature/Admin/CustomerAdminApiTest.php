<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomerGender;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class CustomerAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private $baseUrl = '/api/admin/customers';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_index(): void
    {
        $customers = Customer::factory()->count(5)->create();

        $response = $this->getJson($this->baseUrl);

        $customer = $customers->first();

        $this->assertEquals($customer->id, 1);
        $this->assertCount(5, $customers);

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
                        $json->where('id', $customer->id)
                            ->where('name', $customer->name)
                            ->where('email', $customer->email)
                            ->where('phone', $customer->phone)
                            ->where('gender', $customer->gender->value)
                            ->where('dob', $customer->dob->toISOString())
                            ->missing('photo')
                            ->missing('password')
                            ->has('orders')
                    )
                    ->etc()
            );

        $response->assertOk();
    }

    public function test_store_successfully(): void
    {
        $customer = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => $phone = str_replace('+', '', fake()->unique()->e164PhoneNumber()),
            'dob' => fake()->dateTimeBetween('-35 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(CustomerGender::getAllValues()),
        ];

        $response = $this->postJson($this->baseUrl, $customer);

        $response->assertCreated();

        $this->assertDatabaseHas('customers', $customer);
    }

    public function test_store_validation_errors(): void
    {
        $response = $this->postJson($this->baseUrl, [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => $phone = str_replace('+', '', fake()->unique()->e164PhoneNumber()),
            'dob' => fake()->dateTimeBetween('-35 years', '-18 years')->format('Y-m-d'),
        ]);

        $response->assertInvalid([
            'gender' => 'The gender field is required.',
        ]);

        $response->assertUnprocessable();
    }

    public function test_show(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->getJson($this->baseUrl . '/' . $customer->id);

        $response
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    fn (AssertableJson $json) =>
                    $json->where('id', $customer->id)
                        ->where('name', $customer->name)
                        ->where('email', $customer->email)
                        ->has('orders')
                        ->etc()
                )
            );

        $response->assertOk();
    }

    public function test_show_404(): void
    {
        $this->assertDatabaseEmpty('customers');

        $response = $this->getJson($this->baseUrl . '/' . 100);

        $response->assertNotFound();
    }

    public function test_update_successfully(): void
    {
        $customer = Customer::factory()->create(['name' => 'mock customer name']);

        $this->assertDatabaseHas('customers', ['email' => $customer->email, 'name' => 'mock customer name']);

        $response = $this->putJson($this->baseUrl . '/' . $customer->id, [
            'name' => 'mock customer name updated',
        ]);

        $this->assertDatabaseHas('customers', ['email' => $customer->email, 'name' => 'mock customer name updated']);

        $response
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('data')
                    ->where('data.id', $customer->id)
                    ->where('data.phone', $customer->phone)
                    ->where('data.email', $customer->email)
                    ->where('data.name', 'mock customer name updated')
            );

        $response->assertOk();
    }

    public function test_update_validation_errors(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->putJson($this->baseUrl . '/' . $customer->id, [
            'name' => null,
        ]);

        $response->assertInvalid([
            'name' => 'The name field must be a string.',
        ]);

        $response->assertUnprocessable();
    }

    public function test_destroy(): void
    {
        $customer = Customer::factory()->create();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => $customer->name,
        ]);

        $response = $this->deleteJson($this->baseUrl . '/' . $customer->id);

        $response->assertSuccessful();

        $this->assertSoftDeleted('customers', [
            'id' => $customer->id,
            'name' => $customer->name,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => $customer->name,
        ]);
        $this->assertModelExists($customer);
    }

    public function test_destroy_404(): void
    {
        $this->assertDatabaseEmpty('customers');

        $response = $this->deleteJson($this->baseUrl . '/' . 100);

        $response->assertNotFound();
    }

    public function test_restore(): void
    {
        $customer = Customer::factory()->create();
        $customer->delete();

        $data = [
            'id' => $customer->id,
            'name' => $customer->name,
        ];

        $this->assertModelExists($customer);
        $this->assertDatabaseHas('customers', $data);
        $this->assertSoftDeleted('customers', $data);

        $response = $this->putJson($this->baseUrl . '/' . $customer->id . '/restore');

        $response->assertSuccessful();

        $this->assertNotSoftDeleted('customers', $data);
    }

    public function test_restore_404(): void
    {
        $this->assertDatabaseEmpty('customers');

        $response = $this->putJson($this->baseUrl . '/' . 100 . '/restore');

        $response->assertNotFound();
    }
}
