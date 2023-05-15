<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
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
        // items should be taken from cart
        // total_price should be calculated after
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
