<?php

namespace App\Http\Controllers;

use App\Http\Requests\storeEmployeeRequest;
use App\Models\Employe;
use App\Models\Specialization;
use Hash;
use Illuminate\Http\Request;

class EmployeesController extends Controller
{
    public function createEmployee(storeEmployeeRequest $request)
{
    $validatedData = $request->validated(); 

    $validatedData['password'] = Hash::make($validatedData['password']);

    $employee = Employe::create($validatedData);

    return response()->json([
        'message' => 'User Registered successfully',
        'User' => $employee
    ], 201);
}
public function addEmployee(Request $request){
    $validatedData = $request->validate([ 'name'=>'required|string|max:255',
    'email'=>'required|email|max:255|unique:employes,email',
    'password' =>'required|min:5|string',
    'phone_number'=> 'required|integer|min:10',
    'salary'=> 'required|numeric',
    'birth_day'=> 'required|date',
    'country'=> 'required|string',
    'start_time'=> 'required|string',
    'work_hours'=> 'numeric|required',
    'specialization_id'=> 'required',
]);
    $employee = Employe::create($validatedData);
    return response()->json([
        'message' => 'User Registered successfully',
        'User' => $employee
    ], 201);
}
public function addSpecialization(Request $request){
$validated=$request->validate([
    'specialization'=> 'required|string|max:255',
]);
$specia = Specialization::create($validated);
return response()->json($specia,201);
}
}