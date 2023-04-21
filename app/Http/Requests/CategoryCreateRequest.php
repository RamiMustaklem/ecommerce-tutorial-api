<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryCreateRequest extends FormRequest
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
            'description' => ['required', 'string'],
            // 'image' => ['array:id,original,thumbnail'],
            // 'image.id' => ['required', 'integer', 'exists:attachments,id'],
            // 'image.original' => ['required', 'url', 'exists:attachments,id'],
            // 'image.thumbnail' => ['required', 'url', 'exists:attachments,id'],
        ];
    }
}
