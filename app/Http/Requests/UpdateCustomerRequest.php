<?php

namespace App\Http\Requests;

use App\Enums\CustomerGender;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rules\Enum;

class UpdateCustomerRequest extends FormRequest
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
            'name' => ['string', 'max:255'],
            'email' => [
                'string',
                'email',
                'max:255',
                Rule::unique('customers')->ignore($this->id),
            ],
            // 'password' => [
            //     'confirmed',
            //     Rules\Password::defaults(),
            // ],
            'phone' => [
                'numeric',
                Rule::unique('customers')->ignore($this->id),
            ],
            'gender' => [new Enum(CustomerGender::class)],
            'dob' => ['date'],
        ];
    }
}
