<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private $baseUrl = '/api/products';

    public function test_successfully_get_products_list_without_authentication(): void
    {
        $products = Product::factory()->published()->count(5)->create();
        Product::factory()->published(false)->count(2)->create();

        $this->assertDatabaseCount('products', 7);

        $this->assertGuest();

        $response = $this->getJson($this->baseUrl);

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) =>
                $json
                    ->where('meta.current_page', 1)
                    ->where('meta.total', $products->count())
                    ->has('data', $products->count())
                    ->has(
                        'data',
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
                                    ->has('categories')
                                    ->has('media')
                                    ->missing('orders')
                                    ->missing('order_product')
                                    ->missing('orders_count')
                            )
                    )
                    ->etc()
            );
    }

    public function test_successfully_get_single_product_by_id_without_authentication(): void
    {
        $product = Product::factory()->published()->create();
        $categories = Category::factory(2)->create();
        $product->categories()->attach($categories->pluck('id')->toArray());

        $this->assertGuest();

        $response = $this->getJson($this->baseUrl . '/' . $product->id);

        $response
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    fn (AssertableJson $json) =>
                    $json
                        ->where('id', $product->id)
                        ->where('slug', $product->slug)
                        ->where('name', $product->name)
                        ->where('description', $product->description)
                        ->where('quantity', $product->quantity)
                        ->where('price', $product->price)
                        ->where('old_price', $product->old_price)
                        ->where('is_published', $product->is_published)
                        ->has('media')
                        ->has('categories', $categories->count())
                        ->has(
                            'categories',
                            fn (AssertableJson $json) =>
                            $json
                                ->each(
                                    fn (AssertableJson $json) =>
                                    $json
                                        ->has('id')
                                        ->has('slug')
                                        ->has('name')
                                        ->missing('description')
                                        ->missing('products')
                                )
                        )
                )
            );
    }

    public function test_404_when_get_product_by_id_product_is_not_published(): void
    {
        $product = Product::factory()->published(false)->create();

        $this->assertGuest();

        $response = $this->getJson($this->baseUrl . '/' . $product->id);

        $response->assertNotFound();
    }

    public function test_404_get_single_product_by_id_without_authentication(): void
    {
        $this->assertGuest();

        $response = $this->getJson($this->baseUrl . '/1');

        $response->assertNotFound();
    }
}
