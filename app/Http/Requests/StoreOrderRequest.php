<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'notes' => ['string', 'max:255'],
            'address' => ['required', 'array:street_address,city'],
            'total_price' => ['required', 'decimal:0,2', 'min:0'],
            'order_products' => ['required', 'array', 'min:1', 'exclude'],
            'order_products.*.product_id' => [
                'required', 'integer', 'exists:products,id',
            ],
            'order_products.*.quantity' => [
                'required', 'integer', 'min:1',
            ],
        ];
    }
}
