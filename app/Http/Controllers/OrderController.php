<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
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
        [$pivot, $total_price] = $this->validateAndGenerateOrderProductPivotWithTotalPrice($request);

        $order = DB::transaction(function () use ($request, $pivot, $total_price) {
            $order = Order::create([
                ...$request->safe()->except('order_products'),
                'customer_id' => auth()->id(),
                'total_price' => $total_price,
            ]);

            $order->products()->attach($pivot);

            return $order;
        });

        $this->notifyCustomerAndAdmin($request, $order);

        return new OrderResource($order);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        abort_if(auth()->id() !== $order->customer_id, Response::HTTP_NOT_FOUND, 'Order Not Found.');

        $order->load(['products' => function ($query) {
            $query
                ->select(['slug', 'name', 'excerpt'])
                ->isPublished();
        }]);

        return new OrderResource($order);
    }

    private function notifyCustomerAndAdmin(Request $request, Order $order)
    {
        $customer = $request->user();

        $admin = User::admin()->first();

        Notification::sendNow([$customer, $admin], new OrderCreated($order));
    }

    private function validateAndGenerateOrderProductPivotWithTotalPrice($request): array
    {
        $validator = Validator::make([], [], [], []);

        $order_products = $request->collect('order_products')
            ->mapWithKeys(fn ($item) => [
                $item['product_id'] => $item['quantity']
            ])
            ->toArray();

        $products = Product::whereIn('id', array_keys($order_products))->get();

        $errors = [];

        $pivot = $products->mapWithKeys(function (Product $product, int $key) use ($order_products, &$errors) {

            $product_quantity = $product->quantity;
            $quantity = $order_products[$product->id];

            if (!$product->is_published) {
                $errors["order_products.$key.product_id"] =
                    "The selected product \"$product->name\" is currently unavailable.";
            } else {
                if ($product_quantity < $quantity) {
                    $errors["order_products.$key.quantity"] =
                        "Insufficient remaining quantity for the selected product \"$product->name\". Only \"$product_quantity\" remaining.";
                }
            }

            $unit_price = $product->price;
            return [$product->id => compact('quantity', 'unit_price')];
        });

        $validator->after(function ($validator) use ($errors) {
            if (count($errors)) {
                collect($errors)->each(function ($value, $key) use ($validator) {
                    $validator->errors()->add($key, $value);
                });
            }
        });

        $validator->validate();

        $total_price = $this->calculateOrderProductPivotTotalPrice($pivot);

        return [$pivot, $total_price];
    }

    private function calculateOrderProductPivotTotalPrice($pivot): float
    {
        return $pivot->reduce(
            fn (?int $carry, $item) => $carry + ($item['quantity'] * $item['unit_price']),
            0
        );
    }
}
