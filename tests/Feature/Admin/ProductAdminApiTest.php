<?php

namespace Tests\Feature\Admin;

use App\Models\Attachment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private $baseUrl = '/api/admin/products';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_index(): void
    {
        $products = Product::factory()->count(5)->create();

        $response = $this->getJson($this->baseUrl);

        $first_product = $products->first();

        $this->assertCount(5, $products);

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
                        $json->where('id', $first_product->id)
                            ->where('name', $first_product->name)
                            ->where('slug', $first_product->slug)
                            ->missing('excerpt')
                            ->has('categories')
                            ->missing('media')
                            ->etc()
                    )
                    ->etc()
            );

        $response->assertOk();
    }

    public function test_store_successfully(): void
    {
        $product = [
            'name' => $name = fake()->sentence,
            'slug' => $slug = Str::slug($name),
            'description' => fake()->paragraphs(1, true),
            'is_published' => fake()->boolean(),
            'quantity' => fake()->numberBetween(5, 50),
            'price' => $price = fake()->randomFloat(2, 45, 199),
        ];

        $response = $this->postJson($this->baseUrl, $product);

        $response->assertCreated();

        $this->assertDatabaseHas('products', $product);
    }

    public function test_store_successfully_with_image(): void
    {
        $this->assertAuthenticated();
        Storage::fake('media');
        $file = UploadedFile::fake()->image('image.jpg');
        $file_response = $this->post('/api/admin/attachments', [
            'image' => $file,
        ]);
        $data = $file_response->json('data');
        $id = $data['id'];
        $image = $data['image'];
        $file_response->assertCreated();
        $this->assertDatabaseHas('attachments', compact('id'));
        $this->assertDatabaseHas('media', [
            'name' => 'image',
            'file_name' => 'image.jpg',
            'model_id' => $id,
            'model_type' => Attachment::class,
            'collection_name' => 'default',
        ]);

        $product = [
            'name' => $name = fake()->sentence,
            'slug' => $slug = Str::slug($name),
            'description' => fake()->paragraphs(1, true),
            'is_published' => fake()->boolean(),
            'quantity' => fake()->numberBetween(5, 50),
            'price' => $price = fake()->randomFloat(2, 45, 199),
        ];

        $response = $this->postJson($this->baseUrl, [...$product, 'images' => [compact('id')]]);

        $response->assertCreated();
        $productData = $response->json('data');
        $productId = $productData['id'];

        $this->assertDatabaseHas('products', $product);
        $this->assertDatabaseHas('media', [
            'name' => 'image',
            'file_name' => 'image.jpg',
            'model_id' => $productId,
            'model_type' => Product::class,
            'collection_name' => 'default',
        ]);
    }

    public function test_store_successfully_with_multiple_images(): void
    {
        $this->assertAuthenticated();
        Storage::fake('media');

        // mock file 1
        $file1 = UploadedFile::fake()->image('image1.jpg');
        $file_response1 = $this->post('/api/admin/attachments', [
            'image' => $file1,
        ]);
        $file1_data = $file_response1->json('data');
        $file1_id = $file1_data['id'];
        $file_response1->assertCreated();
        $this->assertDatabaseHas('attachments', ['id' => $file1_id]);
        $this->assertDatabaseHas('media', [
            'name' => 'image1',
            'file_name' => 'image1.jpg',
            'model_id' => $file1_id,
            'model_type' => Attachment::class,
            'collection_name' => 'default',
        ]);

        // mock file 2
        $file2 = UploadedFile::fake()->image('image2.jpg');
        $file_response2 = $this->post('/api/admin/attachments', [
            'image' => $file2,
        ]);
        $file2_data = $file_response2->json('data');
        $file2_id = $file2_data['id'];
        $file_response2->assertCreated();
        $this->assertDatabaseHas('attachments', ['id' => $file2_id]);
        $this->assertDatabaseHas('media', [
            'name' => 'image2',
            'file_name' => 'image2.jpg',
            'model_id' => $file2_id,
            'model_type' => Attachment::class,
            'collection_name' => 'default',
        ]);

        $product = [
            'name' => $name = fake()->sentence,
            'slug' => $slug = Str::slug($name),
            'description' => fake()->paragraphs(1, true),
            'is_published' => fake()->boolean(),
            'quantity' => fake()->numberBetween(5, 50),
            'price' => $price = fake()->randomFloat(2, 45, 199),
        ];

        $response = $this->postJson($this->baseUrl, [
            ...$product,
            'images' => [
                ['id' => $file1_id],
                ['id' => $file2_id],
            ],
        ]);

        $response->assertCreated();
        $productData = $response->json('data');
        $productId = $productData['id'];

        $this->assertDatabaseHas('products', $product);
        $this->assertDatabaseHas('media', [
            'name' => 'image1',
            'file_name' => 'image1.jpg',
            'model_id' => $productId,
            'model_type' => Product::class,
            'collection_name' => 'default',
        ]);
        $this->assertDatabaseHas('media', [
            'name' => 'image2',
            'file_name' => 'image2.jpg',
            'model_id' => $productId,
            'model_type' => Product::class,
            'collection_name' => 'default',
        ]);
    }

    public function test_store_validation_errors(): void
    {
        $response = $this->postJson($this->baseUrl, [
            'name' => $name = fake()->sentence,
            'slug' => Str::slug($name),
            'description' => fake()->paragraphs(1, true),
            'is_published' => fake()->boolean(),
        ]);

        $response->assertInvalid([
            'quantity' => 'The quantity field is required.',
            'price' => 'The price field is required.',
        ]);

        $response->assertUnprocessable();
    }

    public function test_store_validation_errors_images_do_not_exist_and_not_distinct(): void
    {
        $response = $this->postJson($this->baseUrl, [
            'name' => $name = fake()->sentence,
            'slug' => Str::slug($name),
            'description' => fake()->paragraphs(1, true),
            'is_published' => fake()->boolean(),
            'quantity' => fake()->randomNumber(1),
            'price' => fake()->randomFloat(2, 1, 200),
            'images' => [
                ['id' => 100],
                ['id' => 100],
            ],
        ]);

        $response->assertInvalid([
            "images.0.id" => [
                "The selected images.0.id is invalid.",
                "The images.0.id field has a duplicate value.",
            ],
            "images.1.id" => [
                "The selected images.1.id is invalid.",
                "The images.1.id field has a duplicate value.",
            ],
        ]);

        $response->assertUnprocessable();
    }

    public function test_show(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson($this->baseUrl . '/' . $product->id);

        $response
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    fn (AssertableJson $json) =>
                    $json->where('id', $product->id)
                        ->where('name', $product->name)
                        ->where('slug', $product->slug)
                        ->has('categories')
                        ->has('media')
                        ->etc()
                )
                    ->where('data.id', $product->id)
                    ->where('data.name', $product->name)
                    ->where('data.slug', $product->slug)
            );

        $response->assertOk();
    }

    public function test_show_404(): void
    {
        $this->assertDatabaseEmpty('products');

        $response = $this->getJson($this->baseUrl . '/' . 100);

        $response->assertNotFound();
    }

    public function test_update_successfully(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);

        $this->assertDatabaseHas('products', ['slug' => $product->slug, 'quantity' => 10]);

        $response = $this->putJson($this->baseUrl . '/' . $product->id, [
            'quantity' => 0,
        ]);

        $this->assertDatabaseHas('products', ['slug' => $product->slug, 'quantity' => 0]);

        $response
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('data')
                    ->where('data.id', $product->id)
                    ->where('data.name', $product->name)
                    ->where('data.slug', $product->slug)
                    ->where('data.quantity', 0)
            );

        $response->assertOk();
    }

    public function test_update_validation_errors(): void
    {
        $product = Product::factory()->create();

        $response = $this->putJson($this->baseUrl . '/' . $product->id, [
            'description' => null,
        ]);

        $response->assertInvalid([
            'description' => 'The description field must be a string.',
        ]);

        $response->assertUnprocessable();
    }

    public function test_destroy(): void
    {
        $product = Product::factory()->create();

        $this->assertDatabaseHas('products', ['id' => $product->id]);

        $response = $this->deleteJson($this->baseUrl . '/' . $product->id);

        $response->assertSuccessful();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertModelMissing($product);
    }

    public function test_destroy_404(): void
    {
        $this->assertDatabaseEmpty('products');

        $response = $this->deleteJson($this->baseUrl . '/' . 100);

        $response->assertNotFound();
    }
}
