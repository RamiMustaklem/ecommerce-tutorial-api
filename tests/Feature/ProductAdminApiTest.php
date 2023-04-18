<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ProductAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private $baseUrl = '/api/admin/products';

    public function test_index(): void
    {
        $response = $this->getJson($this->baseUrl);

        $response
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('data')
                    ->has('meta')
                    // ->where('id', 1)
                    // ->where('name', 'Victoria Faith')
                    // ->where('email', fn (string $email) => str($email)->is('victoria@gmail.com'))
                    // ->whereNot('status', 'pending')
                    // ->missing('password')
                    ->etc()
            );

        $response->assertStatus(200);
    }

    // public function test_store(): void
    // {
    //     $response = $this->get('/');

    //     $response->assertStatus(200);
    // }
}
