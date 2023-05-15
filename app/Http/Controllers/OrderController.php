<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return OrderResource::collection(
            Order::active()
                ->customer()
                ->with(['products' => function ($query) {
                    $query
                        ->select(['slug', 'name', 'excerpt'])
                        ->isPublished();
                }])
                // ->select(['uuid', 'total_price', 'status', 'notes', 'address'])
                ->paginate()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CheckoutRequest $request)
    {
        $order_products = $request->collect('order_products')
            ->mapWithKeys(fn ($item) => [
                $item['product_id'] => $item['quantity']
            ])
            ->toArray();

        $products = Product::isPublished()
            ->whereIn('id', array_keys($order_products))
            ->get();

        $pivot = $products->mapWithKeys(function ($product) use ($order_products) {
            $quantity = $order_products[$product->id];
            $unit_price = $product->price;
            return [$product->id => compact('quantity', 'unit_price')];
        });

        $total_price = $pivot->reduce(
            fn (?int $carry, $item) => $carry + ($item['quantity'] * $item['unit_price']),
            0
        );

        return DB::transaction(function () use ($request, $pivot, $total_price) {
            $order = Order::create([
                ...$request->safe()->except('order_products'),
                'customer_id' => auth()->id(),
                'total_price' => $total_price,
            ]);

            $order->products()->attach($pivot);

            return new OrderResource($order);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        abort_if(auth()->user()->id !== $order->customer_id, Response::HTTP_NOT_FOUND, 'Order Not Found.');

        $order->load(['products' => function ($query) {
            $query
                ->select(['slug', 'name', 'excerpt'])
                ->isPublished();
        }]);

        return new OrderResource($order);
    }
}
