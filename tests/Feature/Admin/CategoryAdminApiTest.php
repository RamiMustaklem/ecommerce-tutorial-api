<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Support\Str;
use Tests\TestCase;

class CategoryAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private $baseUrl = '/api/admin/categories';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->admin()->create();
        $this->actingAs($user);
    }

    public function test_index_unauthorized_if_not_admin(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertAuthenticated();

        $response = $this->getJson($this->baseUrl);

        $response->assertUnauthorized();
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
                            ->has('products')
                            // ->missing('images')
                            ->etc()
                    )
                    ->etc()
            );

        $response->assertOk();
    }

    public function test_store_successfully(): void
    {
        $category = [
            'name' => $name = fake()->sentence,
            'slug' => $slug = Str::slug($name),
            'description' => fake()->paragraphs(1, true),
        ];

        $response = $this->postJson($this->baseUrl, $category);

        $response->assertCreated();

        $this->assertDatabaseHas('categories', $category);
    }

    public function test_store_validation_errors(): void
    {
        $response = $this->postJson($this->baseUrl, [
            'name' => $name = fake()->sentence,
            'slug' => Str::slug($name),
        ]);

        $response->assertInvalid([
            'description' => 'The description field is required.',
        ]);

        $response->assertUnprocessable();
    }

    public function test_show(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson($this->baseUrl . '/' . $category->id);

        $response
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    fn (AssertableJson $json) =>
                    $json->where('id', $category->id)
                        ->where('name', $category->name)
                        ->where('slug', $category->slug)
                        ->etc()
                )
                    ->where('data.id', $category->id)
                    ->where('data.name', $category->name)
                    ->where('data.slug', $category->slug)
            );

        $response->assertOk();
    }

    public function test_show_404(): void
    {
        $this->assertDatabaseEmpty('categories');

        $response = $this->getJson($this->baseUrl . '/' . 100);

        $response->assertNotFound();
    }

    public function test_update_successfully(): void
    {
        $category = Category::factory()->create(['description' => 'mock category description']);

        $this->assertDatabaseHas('categories', ['slug' => $category->slug, 'description' => 'mock category description']);

        $response = $this->putJson($this->baseUrl . '/' . $category->id, [
            'description' => 'mock category description updated',
        ]);

        $this->assertDatabaseHas('categories', ['slug' => $category->slug, 'description' => 'mock category description updated']);

        $response
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('data')
                    ->where('data.id', $category->id)
                    ->where('data.name', $category->name)
                    ->where('data.slug', $category->slug)
                    ->where('data.description', 'mock category description updated')
            );

        $response->assertOk();
    }

    public function test_update_validation_errors(): void
    {
        $category = Category::factory()->create();

        $response = $this->putJson($this->baseUrl . '/' . $category->id, [
            'description' => null,
        ]);

        $response->assertInvalid([
            'description' => 'The description field must be a string.',
        ]);

        $response->assertUnprocessable();
    }

    public function test_destroy(): void
    {
        $category = Category::factory()->create();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);

        $response = $this->deleteJson($this->baseUrl . '/' . $category->id);

        $response->assertSuccessful();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertModelMissing($category);
    }

    public function test_destroy_404(): void
    {
        $this->assertDatabaseEmpty('categories');

        $response = $this->deleteJson($this->baseUrl . '/' . 100);

        $response->assertNotFound();
    }
}
