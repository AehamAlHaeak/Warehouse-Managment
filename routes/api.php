<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\IDUController;
use App\Http\Controllers\UserContruller;
use App\Http\Controllers\SuperAdmenController;


Route::post("create",[SuperAdmenController::class, "create_new_item"]);
Route::post("create_new_specification",[SuperAdmenController::class, "create_new_specification"]);
Route::post("create_new_employe",[SuperAdmenController::class,"create_new_employe"]);
Route::post("login_employee",[SuperAdmenController::class,'login_employee']);
Route::middleware('auth.api:employee')->group(function () {
    Route::post('logout_employee',[SuperAdmenController::class,'logout_employee']);
});


