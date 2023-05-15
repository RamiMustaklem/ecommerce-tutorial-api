<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            // total_price and order products should be taken from cart
            'cart_id' => ['required', 'integer', 'exists:carts,id'],
            'notes' => ['string', 'max:255'],
            'address' => ['required', 'array:street_address,city'],
            // 'order_products' => ['required', 'array', 'min:1', 'exclude'],
            // 'order_products.*.product_id' => [
            //     'required', 'integer', 'exists:products,id',
            // ],
            // 'order_products.*.quantity' => [
            //     'required', 'integer', 'min:1',
            // ],
        ];
    }
}
