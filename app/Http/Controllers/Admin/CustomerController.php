<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\User;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return CustomerResource::collection(
            User::select(['id', 'name', 'email'])
                ->customer()
                ->with('orders')
                ->paginate()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        $customer = User::create([...$request->validated(), 'password' => Str::random(10)]);

        return new CustomerResource($customer);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $customer)
    {
        $customer->load('orders');

        return new CustomerResource($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, User $customer)
    {
        $customer->update($request->validated());

        return new CustomerResource($customer);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $customer)
    {
        return $customer->delete();
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(int $id)
    {
        $customer = User::withTrashed()->findOrFail($id);
        return $customer->restore();
    }
}
