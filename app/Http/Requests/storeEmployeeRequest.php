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
//eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luX2VtcGxveWUiLCJpYXQiOjE3NDU4NjMwMzksImV4cCI6MTc0NTg2NjYzOSwibmJmIjoxNzQ1ODYzMDM5LCJqdGkiOiJsaHpldmdjdm53NXdWV1c2Iiwic3ViIjoiMiIsInBydiI6IjBiNWY3ODZiM2NhMTg5MzQ1M2NiZGJmYjJkZWU0YmEyZmQzMzJhZmMiLCJpZCI6MiwiZW1haWwiOiJ3d3cuYXNpa0BnbWFpbC5jb20iLCJwaG9uZV9udW1iZXIiOjk4NzY1NDMyMX0.Q5_mTuSsi2jRjjQTS2BJoYZ5ZOz1e98o7chl1UmGBi4