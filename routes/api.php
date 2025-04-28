<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\IDUController;
use App\Http\Controllers\UserContruller;
use App\Http\Controllers\SuperAdmenController;



Route::controller(SuperAdmenController::class)->group(function () {
    Route::post("create_new_warehouse", "create_new_warehouse");
    Route::post("create_new_specification",[SuperAdmenController::class, "create_new_specification"]);
    Route::post("create_new_employe","create_new_employe");
    Route::post("create_new_distribution_center","create_new_distribution_center");
    Route::post("create_new_vehicle","create_new_vehicle");
    Route::post("create_new_supplier","create_new_supplier");
    Route::post("create_new_garage","create_new_garage");

});
//create_new_garage

