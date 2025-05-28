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
            'name' => 'string',
            'last_name' => 'string',
            'location' => 'string',
            'birthday' => 'date',
            'email' => 'email|unique:users,email',
            'phone_number' => 'string|unique:users,phone_number',
            'password' => 'required|string|min:6',
          
          
        ];
    
    }
    public function messages(): array
    {
        return [
            'email.unique' => 'the email is  already used',
        ];
    }
}