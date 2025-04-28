<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\IDUController;
use App\Http\Controllers\UserContruller;
use App\Http\Controllers\SuperAdmenController;





Route::post("login_employee",[SuperAdmenController::class,'login_employee']);
Route::middleware('auth.api:employee')->group(function () {
    Route::post('logout_employee',[EmployeesController::class,'logout_employee']);
});


