<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class CategoryAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private $baseUrl = '/api/admin/categories';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
    }

    public function test_index(): void
    {
        $categories = Category::factory()->count(5)->create();

        $response = $this->getJson($this->baseUrl);

        $first_category = $categories[0];

        $this->assertEquals($first_category->id, 1);
        $this->assertCount(5, $categories);

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
                        $json->where('id', $first_category->id)
                            ->where('name', $first_category->name)
                            ->where('slug', $first_category->slug)
                            // ->missing('images')
                            ->etc()
                    )
                    ->etc()
            );

        $response->assertOk(200);
    }
}
