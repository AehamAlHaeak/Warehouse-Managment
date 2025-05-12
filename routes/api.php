<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\IDUController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserContruller;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\SuperAdmenController;
use App\Http\Controllers\Distribution_Center_controller;
use App\Models\DistributionCenter;

Route::controller(SuperAdmenController::class)->group(function () {
    Route::post("create_new_warehouse", "create_new_warehouse");
    Route::post("create_new_specification", "create_new_specification");

    Route::post("create_new_employe", "create_new_employe");
    Route::post("create_new_distribution_center", "create_new_distribution_center");
    Route::post("create_new_vehicle", "create_new_vehicle");
    Route::post("create_new_supplier", "create_new_supplier");
    Route::post("create_new_garage", "create_new_garage");
    Route::post("create_new_product", "create_new_product");





    Route::post("correct_errors", "correct_errors");

    Route::post("create_new_import_jop", "create_new_import_jop");
    Route::post("suppourt_new_product", "suppourt_new_product");
    Route::post("support_new_product_in_place", "support_new_product_in_place");
    Route::get("show_products", "show_products");
    Route::post("/orded_locations", "orded_locations");


});
//orded_locations

Route::post("login_employe", [EmployeController::class, 'login_employe']);
Route::middleware('auth.api:employee')->group(function () {
    Route::post('logout_employe', [SuperAdmenController::class, 'logout_employe']);
});


Route::controller(UserController::class)->group(function () {
    Route::post('register_user', 'register_user');
    Route::post('login_user', 'login_user');
    Route::post('near_by_centers', 'near_by_centers');
    Route::middleware('auth.api')->group(function () {
        Route::post('logout_user', 'logout_user');
        Route::post('updateUser', 'updateUser
        ');

    });
});




Route::middleware("is_distrebution_center_manager")->controller(Distribution_Center_controller::class)->group(function () {

   Route::get("show_my_suppurted_products","show_my_suppurted_products");

});