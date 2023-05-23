<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $published_products_count = Product::isPublished()->count();
        $unpublished_products_count = Product::isPublished(false)->count();
        $products_count = [
            'published' => $published_products_count,
            'unpublished' => $unpublished_products_count,
        ];
        $orders_count = Order::count();
        $customers_count = User::customer()->count();

        $outstanding_orders = Order::isNew()
            ->latest()
            ->select(['id', 'uuid', 'total_price'])
            ->take(10)
            ->get();

        // TODO: return total quantity of sold products with the list - total order quantity where order status = delivered
        $popular_products = Product::isPublished()
            ->has('orders', '>=', 5)
            ->select(['id', 'name', 'slug', 'quantity', 'price', 'old_price'])
            ->take(10)
            ->get();

        $short_products = Product::where('quantity', '<=', 5)
            ->select(['id', 'name', 'slug', 'quantity', 'price', 'old_price', 'is_published'])
            ->take(10)
            ->get();

        return response()->json(
            compact(
                'products_count',
                'orders_count',
                'customers_count',
                'outstanding_orders',
                'popular_products',
                'short_products'
            )
        );
    }
}
