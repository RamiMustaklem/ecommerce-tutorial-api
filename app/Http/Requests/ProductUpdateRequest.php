<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
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
            'name' => ['max:255'],
            'slug' => ['max:255', Rule::unique('products')->ignore($this->id)],
            'excerpt' => ['nullable', 'string'],
            'description' => ['string'],
            'is_published' => ['boolean'],
            'quantity' => ['integer', 'min:0'],
            'price' => ['decimal:0,2', 'min:0'],
            'old_price' => ['decimal:0,2', 'min:0', 'lt:price'],
            'images' => ['array'],
            'images.*.id' => ['required', 'integer', 'exists:attachments,id', 'distinct'],
        ];
    }
}
