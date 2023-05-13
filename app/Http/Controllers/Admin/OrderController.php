<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return OrderResource::collection(
            Order::with(['customer:id,name,email', 'products'])
                ->paginate()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $order = DB::transaction(function () use ($request) {
            $product_ids = $request->collect('order_products');

            $products = Product::whereIn(
                'id',
                $product_ids->pluck('product_id')
            )->get();

            $orderProducts = $product_ids->mapWithKeys(function (array $item) use ($products) {
                return [
                    $item['product_id'] => [
                        'quantity' => $item['quantity'],
                        'unit_price' => $products
                            ->firstWhere('id', $item['product_id'])
                            ->price,
                    ]
                ];
            });

            $orderTotal = $orderProducts->reduce(function (int $carry, array $value) {
                return $carry + ($value['quantity'] * $value['unit_price']);
            }, 0);

            $request->merge(['total_price' => $orderTotal]);

            $order = Order::create($request->validated());

            $order->products()->attach($orderProducts->toArray());

            return $order;
        });

        return new OrderResource($order);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load(['customer', 'products']);

        return new OrderResource($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        DB::transaction(function () use ($request, $order) {
            $product_ids = $request->collect('order_products');

            $products = Product::whereIn(
                'id',
                $product_ids->pluck('product_id')
            )->get();

            $orderProducts = $product_ids->mapWithKeys(function (array $item) use ($products) {
                return [
                    $item['product_id'] => [
                        'quantity' => $item['quantity'],
                        'unit_price' => $products
                            ->firstWhere('id', $item['product_id'])
                            ->price,
                    ]
                ];
            });

            $orderTotal = $orderProducts->reduce(function (int $carry, array $value) {
                return $carry + ($value['quantity'] * $value['unit_price']);
            }, 0);

            $request->merge(['total_price' => $orderTotal]);

            $order->update($request->validated());

            $order->products()->sync($orderProducts->toArray());
        });

        return new OrderResource($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        return $order->delete();
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(int $id)
    {
        $order = Order::withTrashed()->findOrFail($id);
        return $order->restore();
    }
}
