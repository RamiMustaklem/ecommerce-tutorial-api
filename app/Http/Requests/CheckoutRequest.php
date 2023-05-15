<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->role === UserRole::CUSTOMER;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'notes' => ['string', 'max:255'],
            'address' => ['required', 'array:street_address,city'],
            'order_products' => ['required', 'array', 'min:1'],
            'order_products.*.product_id' => [
                'required', 'integer', 'exists:products,id',
            ],
            'order_products.*.quantity' => [
                'required', 'integer', 'min:1',
            ],
        ];
    }
}
