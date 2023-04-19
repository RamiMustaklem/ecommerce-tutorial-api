<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductCreateRequest extends FormRequest
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
            'name' => ['required', 'max:255'],
            'slug' => ['required', 'max:255', 'unique:products'],
            'excerpt' => ['nullable', 'string'],
            'description' => ['required', 'string'],
            'is_published' => ['required', 'boolean'],
            'quantity' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'decimal:0,2', 'min:0'],
            'old_price' => ['nullable', 'decimal:0,2', 'min:0', 'lt:price'],
            // 'images' => ['array:id,original,thumbnail'],
            // 'images.id' => ['required', 'integer', 'exists:attachments,id'],
            // 'images.original' => ['required', 'url', 'exists:attachments,id'],
            // 'images.thumbnail' => ['required', 'url', 'exists:attachments,id'],
        ];
    }
}
