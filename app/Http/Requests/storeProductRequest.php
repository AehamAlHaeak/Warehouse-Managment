<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class storeProductRequest extends FormRequest
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
            'name'=>'required|string|max:128',
            'description'=>'required|string|max:512',
           'img_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'expiration'=>'required|date',
            'producted_in'=>'required|date',
            'import_cycle'=>'nullable|numeric',
            'unit'=>'required|string',
            'price_unit'=>'required|numeric',
            'average'=>'required|numeric',
            'variance'=>'required|numeric',
            'type_id'=> 'required',
        ];
    }
}
