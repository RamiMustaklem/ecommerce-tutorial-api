<?php

namespace App\Http\Requests;

use App\Enums\CustomerGender;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rules\Enum;

class StoreCustomerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:' . Customer::class,
            ],
            // 'password' => [
            //     'required',
            //     'confirmed',
            //     Rules\Password::defaults(),
            // ],
            'phone' => [
                'required',
                'numeric',
                'unique:' . Customer::class,
            ],
            'gender' => [
                'required',
                new Enum(CustomerGender::class),
            ],
            'dob' => ['required', 'date'],
        ];
    }
}
