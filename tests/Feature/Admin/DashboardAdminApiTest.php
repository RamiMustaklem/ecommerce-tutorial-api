<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private $baseUrl = '/api/admin/dashboard';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->admin()->create();
        $this->actingAs($user, 'sanctum');
    }

    public function test_dashboard_unauthorized_if_not_admin(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertAuthenticated();

        $response = $this->getJson($this->baseUrl);

        $response->assertUnauthorized();
    }

    public function test_no_data_return_successfully(): void
    {
        $this->assertAuthenticated();

        $response = $this->getJson($this->baseUrl);

        $response->assertJsonPath('products_count.published', 0);
        $response->assertJsonPath('products_count.unpublished', 0);
        $response->assertJsonPath('orders_count', 0);
        $response->assertJsonPath('customers_count', 0);
        $response->assertJsonPath('short_products', []);
        $response->assertJsonPath('outstanding_orders', []);
        $response->assertJsonCount(0, 'outstanding_orders');
        $response->assertJsonCount(0, 'popular_products');
        $response->assertJsonPath('popular_products', []);
    }

    public function test_data_return_successfully(): void
    {
        $this->assertAuthenticated();

        $this->artisan('db:seed');

        $response = $this->getJson($this->baseUrl);

        $published_products_count = DB::table('products')->where('is_published', true)->count();
        $unpublished_products_count = DB::table('products')->where('is_published', false)->count();
        $orders_count = DB::table('orders')->count();
        $customers_count = DB::table('users')->where('role', 'customer')->count();
        $db_short_products_count = DB::table('products')->where('quantity', '<=', 5)->count();
        $db_outstanding_orders_count = DB::table('orders')->where('status', OrderStatus::NEW->value)->count();

        $popular_products = Product::isPublished()
            ->has('orders', '>=', 5)
            ->select(['id', 'name', 'slug', 'quantity', 'price', 'old_price'])
            ->take(10)
            ->get();

        $response->assertJsonPath('products_count.published', $published_products_count);
        $response->assertJsonPath('products_count.unpublished', $unpublished_products_count);
        $response->assertJsonPath('orders_count', $orders_count);
        $response->assertJsonPath('customers_count', $customers_count);
        $response->assertJsonCount($db_short_products_count, 'short_products');
        $response->assertJsonCount($db_outstanding_orders_count > 10 ? 10 : $db_outstanding_orders_count, 'outstanding_orders');
        $response->assertJsonCount($popular_products->count() > 10 ? 10 : $popular_products->count(), 'popular_products');
        $response->assertJsonPath('popular_products', $popular_products->toArray());
    }
}
