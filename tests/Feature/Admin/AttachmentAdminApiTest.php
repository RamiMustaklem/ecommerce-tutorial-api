<?php

namespace Tests\Feature\Admin;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private $baseUrl = '/api/admin/attachments';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_store_successfully(): void
    {
        $this->assertAuthenticated();

        Storage::fake('media');

        $file = UploadedFile::fake()->image('image.jpg');

        $response = $this->post($this->baseUrl, [
            'image' => $file,
        ]);

        $data = $response->json('data');
        $id = $data['id'];
        $image = $data['image'];

        $response->assertCreated();

        $this->assertDatabaseHas('attachments', compact('id'));
        $this->assertDatabaseHas('media', [
            'name' => 'image',
            'file_name' => 'image.jpg',
            'model_id' => $id,
            'model_type' => Attachment::class,
            'collection_name' => 'default',
        ]);
        $this->assertIsArray($image);
        $this->assertArrayHasKey('original', $image);
        $this->assertArrayHasKey('thumbnail', $image);
        $this->assertEquals([
            'original' => 'http://localhost/storage/1/image.jpg',
            'thumbnail' => 'http://localhost/storage/1/conversions/image-thumbnail.jpg',
        ], $image);
    }

    public function test_store_validation_errors_large_size(): void
    {
        $this->assertAuthenticated();

        Storage::fake('media');

        $file = UploadedFile::fake()->image('image.jpg')->size(2048);

        $response = $this->post($this->baseUrl, [
            'image' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertInvalid([
            'image' => 'The image field must not be greater than 1024 kilobytes.',
        ]);

        $response->assertUnprocessable();
    }

    public function test_store_validation_errors_file_type(): void
    {
        $this->assertAuthenticated();

        Storage::fake('media');

        $file = UploadedFile::fake()->create('document.pdf', 512, 'application/pdf');

        $response = $this->post($this->baseUrl, [
            'image' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertInvalid([
            'image' => [
                'The image field must be an image.',
                'The image field must be a file of type: png, jpg.',
            ],
        ]);

        $response->assertUnprocessable();
    }

    public function test_destroy(): void
    {
        $this->assertAuthenticated();

        Storage::fake('media');

        $file = UploadedFile::fake()->image('image.jpg');

        $response = $this->post($this->baseUrl, [
            'image' => $file,
        ]);

        $data = $response->json('data');
        $id = $data['id'];
        $image = $data['image'];

        $response->assertCreated();

        $this->assertDatabaseHas('attachments', compact('id'));

        $media = [
            'name' => 'image',
            'file_name' => 'image.jpg',
            'model_id' => $id,
            'model_type' => Attachment::class,
            'collection_name' => 'default',
        ];

        $this->assertDatabaseHas('media', $media);

        $response = $this->deleteJson($this->baseUrl . '/' . $id);
        $response->assertSuccessful();

        $this->assertDatabaseMissing('attachments', compact('id'));
        $this->assertDatabaseMissing('media', $media);
    }

    public function test_destroy_404(): void
    {
        $this->assertDatabaseEmpty('attachments');

        $response = $this->deleteJson($this->baseUrl . '/' . 100);

        $response->assertNotFound();
    }
}
