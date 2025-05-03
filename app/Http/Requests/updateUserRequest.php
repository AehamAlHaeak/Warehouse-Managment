<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class updateUserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'location' => 'sometimes|string',
            'birthday' => 'sometimes|date',
            'email' => 'sometimes|email|unique:users,email',
            'phone_number' => 'sometimes|string|unique:users,phone_number',
            'password' => 'sometimes|string|min:6',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,bmp|max:4096',
            'creditCards' => 'sometimes|json',
        ];
    }
}
