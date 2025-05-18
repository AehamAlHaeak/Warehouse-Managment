<?php


use App\Http\Controllers\Movecontroller;
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
   
    Route::post("create_new_supplier", "create_new_supplier");
    Route::post("create_new_garage", "create_new_garage");
   
    Route::post("suppurt_new_storage_media", "suppurt_new_storage_media");  
    Route::post("create_new_imporet_op_storage_media","create_new_imporet_op_storage_media");
    
    Route::post("accept_import_op_storage_media", "accept_import_op_storage_media");
    
    Route::post("reject_import_op_storage_media", "reject_import_op_storage_media");
    
    Route::post("support_new_container", "support_new_container");
  
     Route::get("show_latest_import_op_storage_media","show_latest_import_op_storage_media");

    Route::post("create_new_import_operation_product", "create_new_import_operation_product");
    Route::post("suppourt_new_product", "suppourt_new_product");
    Route::post("create_import_op_vehicles", "create_import_op_vehicles");
    Route::get("show_products", "show_products");
    Route::post("orded_locations", "orded_locations");
    Route::get("creeate_bill","creeate_bill");
    Route::post("create_new_section","create_new_section");
    Route::post("add_new_supplies_to_supplier","add_new_supplies_to_supplier");
    Route::get("show_suppliers", "show_suppliers");
    Route::get("show_products_of_supplier/{id}", "show_products_of_supplier");
    Route::get("show_suppliers_of_product/{id}", "show_suppliers_of_product");
    Route::get("show_storage_media_of_supplier/{id}", "show_storage_media_of_supplier");

});
//show_latest_import_op_storage_media
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

//creeate_bil
//mean that the products recievd successfully 
Route::get('confirmReception',[Movecontroller::class,'confirmReception']);
