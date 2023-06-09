<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateOrderRequest extends FormRequest
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
            'customer_id' => ['missing', 'exclude'],
            'notes' => ['string', 'max:255', 'nullable'],
            'address' => ['array:street_address,city'],
            'total_price' => ['decimal:0,2', 'min:0'],
            'status' => ['required', new Enum(OrderStatus::class)],
            'order_products' => ['array', 'min:1', 'exclude'],
            'order_products.*.product_id' => [
                'required', 'integer', 'exists:products,id',
            ],
            'order_products.*.quantity' => [
                'required', 'integer', 'min:1',
            ],
        ];
    }
}
