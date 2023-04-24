<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return CustomerResource::collection(
            Customer::with('orders')->paginate()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create($request->validated());

        return new CustomerResource($customer);
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        $customer->load('orders');

        return new CustomerResource($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return new CustomerResource($customer);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        return $customer->delete();
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(int $id)
    {
        $customer = Customer::withTrashed()->findOrFail($id);
        return $customer->restore();
    }
}
