<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class storeUserRequest extends FormRequest
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
            'name' => 'required|string',
            'last_name' => 'required|string',
            'location' => 'required|string',
            'birthday' => 'nullable|date',
            'email' => 'nullable|email|unique:users,email',
            'phone_number' => 'nullable|string|unique:users,phone_number',
            'password' => 'required|string|min:6',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,bmp|max:4096',
            'creditCards' => 'nullable|json',
        ];
    }
    public function messages(): array
    {
        return [
            'email.unique' => 'the email is  already used',
        ];
    }
}