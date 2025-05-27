<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\IDUController;
use App\Http\Controllers\SuperAdmenController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::controller(SuperAdmenController::class)->group(function () {
    Route::post("create_new_warehouse", "create_new_warehouse");
    Route::post("create_new_specification", "create_new_specification");

    Route::post("create_new_employe", "create_new_employe");
    Route::post("create_new_distribution_center", "create_new_distribution_center");

    Route::post("create_new_supplier", "create_new_supplier");
    Route::post("create_new_garage", "create_new_garage");

    Route::post("suppurt_new_storage_media", "suppurt_new_storage_media");
    Route::post("create_new_imporet_op_storage_media", "create_new_imporet_op_storage_media");

    Route::post("accept_import_op_storage_media", "accept_import_op_storage_media");



    Route::post("support_new_container", "support_new_container");

    Route::get("show_latest_import_op_storage_media", "show_latest_import_op_storage_media");

    Route::post("create_new_import_operation_product", "create_new_import_operation_product");

    Route::post("accept_import_op_products", "accept_import_op_products");
    Route::post("reject_import_op", "reject_import_op");
    Route::get("show_latest_import_op_products", "show_latest_import_op_products");

    Route::get("show_warehouses_of_product/{id}", "show_warehouses_of_product");


    Route::post("suppourt_new_product", "suppourt_new_product");
    Route::post("create_import_op_vehicles", "create_import_op_vehicles");


     Route::post("accept_import_op_vehicles", "accept_import_op_vehicles");

    Route::post("edit_product", "edit_product");
    Route::get("show_places_of_products/{product_id}", "show_places_of_products");
    Route::get("delete_product/{product_id}", "delete_product");


    Route::get("show_products", "show_products");
    Route::post("orded_locations", "orded_locations");
    Route::get("creeate_bill", "creeate_bill");
    Route::post("create_new_section", "create_new_section");
    Route::post("add_new_supplies_to_supplier", "add_new_supplies_to_supplier");
    Route::get("show_suppliers", "show_suppliers");
    Route::get("show_products_of_supplier/{id}", "show_products_of_supplier");
    Route::get("show_suppliers_of_product/{id}", "show_suppliers_of_product");
    Route::get("show_storage_media_of_supplier/{id}", "show_storage_media_of_supplier");
});