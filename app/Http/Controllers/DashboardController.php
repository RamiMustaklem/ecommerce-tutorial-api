<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
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
        $customers_count = Customer::count();

        $outstanding_orders = Order::isNew()->select(['id', 'uuid', 'total_price'])->paginate();

        // TODO: return total quantity of sold products with the list - total order quantity where order status = delivered
        $popular_products = Product::isPublished()
            ->has('orders', '>=', 5)
            ->select(['id', 'name', 'slug', 'quantity', 'price', 'old_price'])
            ->paginate();

        $short_products = Product::where('quantity', '<=', 5)
            ->select(['id', 'name', 'slug', 'quantity', 'price', 'old_price', 'is_published'])
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
