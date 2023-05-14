<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private $baseUrl = '/api/categories';

    public function test_successfully_get_categories_list_without_authentication(): void
    {
        $categories = Category::factory()->count(5)->create();
        $category = $categories->first();

        $this->assertGuest();

        $response = $this->getJson($this->baseUrl);

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.id', $category->id)
            ->assertJsonPath('data.0.slug', $category->slug)
            ->assertJsonPath('data.0.name', $category->name)
            ->assertJsonPath('data.0.description', $category->description)
            ->assertJsonPath('data.0.image', $category->image)
            ->assertJsonMissingPath('data.0.products');
    }

    public function test_successfully_get_single_category_by_id_without_authentication(): void
    {
        $category = Category::factory()->create();
        $products = Product::factory(4)->create();
        $category->products()->attach($products->pluck('id')->toArray());
        $published_products_count = $products->filter(fn ($product) => $product->is_published)->count();

        $this->assertGuest();

        $response = $this->getJson($this->baseUrl . '/' . $category->id);

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    fn (AssertableJson $json) =>
                    $json
                        ->where('id', $category->id)
                        ->where('slug', $category->slug)
                        ->where('name', $category->name)
                        ->where('description', $category->description)
                        ->where('image', $category->image)
                        ->has('products', $published_products_count)
                        ->has(
                            'products',
                            fn (AssertableJson $json) =>
                            $json
                                ->each(
                                    fn (AssertableJson $json) =>
                                    $json
                                        ->has('id')
                                        ->has('slug')
                                        ->has('name')
                                        ->has('description')
                                        ->has('quantity')
                                        ->has('price')
                                        ->has('old_price')
                                        ->where('is_published', true)
                                        ->missing('categories')
                                )
                        )
                )
            );
    }

    public function test_404_get_single_category_by_id_without_authentication(): void
    {
        $this->assertGuest();

        $response = $this->getJson($this->baseUrl . '/1');

        $response->assertNotFound();
    }
}
