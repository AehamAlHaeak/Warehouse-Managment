<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\IDUController;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\WarehouseController;
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
Route::post("start_application", [SuperAdmenController::class, "start_application"]);

Route::controller(SuperAdmenController::class)->middleware('is_super_admin')->group(function () {

    //create type of prods or specializations of employes

    Route::post("create_new_specification", "create_new_specification");

    //types configrations and featurs 

    Route::get("show_all_types", "show_all_types");

    Route::post("edit_type", "edit_type");

    Route::get("show_products_of_type/{type_id}", "show_products_of_type");

    Route::get("show_warehouse_of_type/{type_id}", "show_warehouse_of_type");

    Route::get("delete_type/{type_id}", "delete_type");
    //end
    //Specializations configration 
    Route::get("show_all_specializations", "show_all_specializations");

    Route::get("delete_Specialization/{spec_id}", "delete_Specialization");

    Route::post("edit_Specialization", "edit_Specialization");

    Route::get("show_employees_of_spec/{spec_id}", "show_employees_of_spec");

    //end
    //show_all_specializations

    //products page apis
    Route::get("show_products", "show_products");

    Route::get("show_places_of_products/{product_id}", "show_places_of_products");

    Route::post("support_new_product", "suppourt_new_product");

    Route::post("edit_product", "edit_product");

    Route::get("delete_product/{product_id}", "delete_product");
    // end 
    //create logistic things 

    Route::post("edit_storage_media", "edit_storage_media");

    Route::post("edit_continer", "edit_continer");
    //end

    //constract the structur  delete_warehouse
    Route::post("create_new_warehouse", "create_new_warehouse");

    Route::post("edit_warehouse", "edit_warehouse");

    Route::get("delete_warehouse/{warehouse_id}", "delete_warehouse");

    Route::post("create_new_distribution_center", "create_new_distribution_center");

    Route::post("edit_distribution_center", "edit_distribution_center");
     
    Route::get("delete_distribution_center/{dest_id}", "delete_distribution_center");

    Route::post("create_new_garage", "create_new_garage");

    Route::post("edit_garage", "edit_garage");

    Route::get("delete_garage/{garage_id}", "delete_garage");
    //employees configrations 
    Route::get("show_all_employees", "show_all_employees");

    Route::post("create_new_employe", "create_new_employe");

    Route::post("edit_employe", "edit_employe");

    Route::get("cancel_employe/{emp_id}", "cancel_employe");

    //end 
    Route::post("create_new_section", "create_new_section");

    Route::post("edit_section", "edit_section");

    Route::get("delete_section/{sec_id}", "delete_section");
    //end  cancel_employe/{$emp_id}


    //suppliers config
    Route::get("show_suppliers", "show_suppliers");

    Route::post("create_new_supplier", "create_new_supplier");

    Route::post("add_new_supplies_to_supplier", "add_new_supplies_to_supplier");
     
    Route::post("edit_supplier", "edit_supplier");

    Route::get("delete_supplier/{supplier_id}", "delete_supplier");

    Route::get("delete_supplies_from_supplier/{supplies_id}", "delete_supplies_from_supplier");
    
    //end 
    //show_sections_of_storage_media_on_warehouse($storage_media_id,$warehouse_id)
    //import operation storage media operations 
    //this api is public can use it in difirent cases here and on show in supplier details
    Route::get("show_storage_media_of_supplier/{id}", "show_storage_media_of_supplier");

    Route::get("show_supplier_of_storage_media/{storage_media_id}", "show_supplier_of_storage_media");

    Route::get("show_sections_of_storage_media_on_warehouse/{storage_media_id}/{warehouse_id}", "show_sections_of_storage_media_on_warehouse");

    Route::get("show_warehouse_of_storage_media/{storage_media_id}", "show_warehouse_of_storage_media");

    Route::post("create_new_imporet_op_storage_media", "create_new_imporet_op_storage_media");

    Route::get("show_latest_import_op_storage_media", "show_latest_import_op_storage_media");


    Route::post("accept_import_op_storage_media", "accept_import_op_storage_media");
    //  end
    Route::post("create_import_op_vehicles", "create_import_op_vehicles");

    Route::post("accept_import_op_vehicles", "accept_import_op_vehicles");

    //import operation product operations 
    //this api is public can use it in difirent cases here and on show in supplier details
    Route::post("create_new_import_operation_product", "create_new_import_operation_product");

    Route::get("show_products_of_supplier/{id}", "show_products_of_supplier");

    Route::get("show_suppliers_of_product/{id}", "show_suppliers_of_product");

    Route::get("show_warehouses_of_product/{id}", "show_warehouses_of_product");

    Route::post("accept_import_op_products", "accept_import_op_products");

    Route::get("show_latest_import_op_products", "show_latest_import_op_products");
    //end
    Route::post("reject_import_op", "reject_import_op");
});
//show_warehouse_of_storage_media($storage_media_id)

Route::controller(WarehouseController::class)->group(function () {

    Route::get("show_distrebution_centers_of_product/{warehouse_id}/{product_id}", "show_distrebution_centers_of_product");
    Route::get("show_distribution_centers_of_storage_media_in_warehouse/{warehouse_id}/{storage_media_id}", "show_distribution_centers_of_storage_media_in_warehouse");
});
//show_distribution_centers_of_storage_media_in_warehouse($warehouse_id,$storage_media_id)


Route::post("login_employe", [EmployeController::class, 'login_employe']);