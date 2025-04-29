<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class storeEmployeeRequest extends FormRequest
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
            "name"=>"required",
            "email"=>"required|email",
            "password"=>"required|min:8",
            "phone_number"=>"required",
            "specialization_id"=>"required|integer",
            "salary"=>"required",
            "birth_day"=>"date",
            "country"=>"required",
            "start_time"=>"required",
            "work_hours"=>"required|integer|max:10",
            "workable_type"=>"in:Warehouse,DistributionCenter",
            "workable_id"=>"integer"
        ];
    }
}
