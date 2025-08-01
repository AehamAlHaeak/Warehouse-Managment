<?php


use App\Http\Controllers\Movecontroller;
use App\Http\Controllers\WarehouseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\IDUController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserContruller;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\SuperAdmenController;
use App\Http\Controllers\Distribution_Center_controller;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\QAController;
use App\Http\Controllers\ViolationController;
use App\Models\DistributionCenter;


Route::post("start_application", [SuperAdmenController::class, "start_application"]);


Route::controller(SuperAdmenController::class)->middleware('is_super_admin')->group(function () {

    //create type of prods or specializations of employes


    Route::post("create_new_type", "create_new_type");

    Route::post("create_new_specialization", "create_new_specialization");
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

    Route::post("edit_storage_media", "edit_storage_media");

    Route::post("edit_continer", "edit_continer");

    Route::get("show_storage_media_of_product/{product_id}", "show_storage_media_of_product");

    Route::get("show_container_of_product/{product_id}", "show_container_of_product");
    //end

    //constract the structur  delete_warehouse
    Route::get("show_all_warehouses", "show_all_warehouses");

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
    //  end  show_latest_import_op_vehicles
    Route::post("create_import_op_vehicles", "create_import_op_vehicles");

    Route::post("accept_import_op_vehicles", "accept_import_op_vehicles");

    Route::get("show_latest_import_op_vehicles", "show_latest_import_op_vehicles");



    //import operation product operations
    //this api is public can use it in difirent cases here and on show in supplier details
    Route::post("create_new_import_operation_product", "create_new_import_operation_product");

    Route::get("show_products_of_supplier/{id}", "show_products_of_supplier");

    Route::get("show_suppliers_of_product/{id}", "show_suppliers_of_product");

    Route::get("show_warehouses_of_product/{id}", "show_warehouses_of_product");

    Route::post("accept_import_op_products", "accept_import_op_products");

    Route::get("show_latest_import_op_products", "show_latest_import_op_products");

    Route::get("import_archive_for_supplier/{sup_id}", "import_archive_for_supplier");

    Route::get("show_import_opreation_content/{imp_op_id}", "show_import_opreation_content");
    //end
    Route::post("reject_import_op", "reject_import_op");

    Route::get("try_choise_trucks/{warehouse_id}/{import_operation_id}", "try_choise_trucks");
    Route::get("resive_notification", "resive_notification");
   
});
//show_import_opreation_content($imp_op_id)

Route::controller(WarehouseController::class)->middleware('is_warehouse_admin')->group(function () {

    Route::get("show_distrebution_centers_of_product/{warehouse_id}/{product_id}", "show_distrebution_centers_of_product");
    Route::get("show_distribution_centers_of_storage_media_in_warehouse/{warehouse_id}/{storage_media_id}", "show_distribution_centers_of_storage_media_in_warehouse");
    Route::post("send_products_from_To", "send_products_from_To");
    Route::get("show_distrebution_centers_of_warehouse/{warehouse_id}", "show_distrebution_centers_of_warehouse");
    Route::get("calculate_ready_vehicles/{warehouse_id}", "calculate_ready_vehicles");
}); //remove_driver(Request $request,$vehicle_id)


Route::controller(Distribution_Center_controller::class)->middleware("is_dist_c_admin")->group(function () {

    Route::get("show_employees_on_place/{place_type}/{place_id}");

    Route::get("show_garages_on_place/{place_type}/{place_id}", "show_garages_on_place");

    Route::get("show_vehicles_of_garage/{garage_id}", "show_vehicles_of_garage");

    Route::get("show_products_of_place/{place_type}/{place_id}", "show_products_of_place");

    Route::get("show_my_work_place", "show_my_work_place");

    Route::get("remove_driver/{vehicle_id}", "remove_driver");

    Route::post("set_driver_for_vehicle", "set_driver_for_vehicle");
});
//set_driver_for_vehicle

Route::controller(Distribution_Center_controller::class)->middleware('is_QA')->group(function () {
    Route::get("show_actual_loads", "show_actual_loads");
    Route::get("show_load_details/{load_id}", "show_load_details");
    Route::get("show_container_details/{load_id}", "show_container_details");
    Route::post("reject_content_from_continer", "reject_content_from_continer");
    Route::get("accept_continer/{container_id}", "accept_continer");
    Route::get("show_sections_on_place/{place_type}/{place_id}", "show_sections_on_place");
    Route::get("show_storage_elements_on_section/{section_id}", "show_storage_elements_on_section");
    Route::get("show_continers_on_storage_element/{storage_element_id}", "show_continers_on_storage_element");
    Route::post("move_containers", "move_containers");
    Route::get("show_incoming_transfers/{place_type}/{place_id}", "show_incoming_transfers");
    Route::get("show_empty_posetions_on_storage_element/{storage_element_id}", "show_empty_posetions_on_storage_element");
    Route::post("move_to_position", "move_to_position");
    Route::post("pass_load", "pass_load");
    Route::post("reset_conditions_in_place", "reset_conditions_in_place");
    Route::post("reject_continer", "reject_continer");
    Route::post("serch", "search");
    Route::get("show_continer_movments/{continer_id}", "show_continer_movments");
});
//resive_notification
Route::post("login_employe", [EmployeController::class, 'login_employe']);

Route::controller(EmployeController::class)->middleware('is_employe')->group(function () {

    Route::get("logout_employe",  'logout_employe');
});
Route::controller(DriverController::class)->middleware('is_driver')->group(function () {

    Route::get("show_my_curent_transfers", "show_my_curent_transfers");
    Route::get("set_status_my_transfer", "set_status_my_transfer");
});

Route::controller(ViolationController::class)->group(function () {

    Route::post("set_conditions", "set_conditions");
});





Route::controller(UserController::class)->group(function () {
    Route::post('register_user', 'register_user');
    Route::post('login_user', 'login_user');


    Route::middleware('auth.api')->group(function () {
        Route::post('logout_user', 'logout_user');

        Route::post('updateUser', 'updateUser');

        Route::post("reserve_products", "reserve_products");

        Route::get("delete_invoice/{invoice_id}", "delete_invoice");

        Route::get("show_products_in_centers", "show_products_in_centers");

        Route::get("show_distribution_centers_of_product_sorted/{product_id}/{longitude}/{latitude}", "show_distribution_centers_of_product_sorted");

        Route::get("show_products_of_distribution_center/{dist_c_id}", "show_products_of_distribution_center");

        Route::get("show_my_invoices", "show_my_invoices");

        Route::get("show_invoice_loads/{invoice_id}", "show_invoice_loads");

        Route::get("delete_load/{load_id}", "delete_load");

        Route::post("edit_load", "edit_load");

        Route::get("execute_invoice/{invoice_id}", "execute_invoice");
    });
}); // execute_invoice($invoice_id)


//creeate_bil

//mean that the products recievd successfully



Route::prefix('warehouses')->group(function () {
    Route::get('{id}/employees', [WarehouseController::class, 'showEmployees']);
    Route::get('{id}/type', [WarehouseController::class, 'showType']);
    Route::get('{id}/sections', [WarehouseController::class, 'showSections']);
    Route::get('{id}/garages', [WarehouseController::class, 'showGarage']);
    Route::get('{id}/supported-products', [WarehouseController::class, 'showprod_In_Warehouse']);
    Route::get('{id}/vehOnGar', [WarehouseController::class, 'showVehicles_OnGarage']);
    Route::get('{id}/storage-media', [WarehouseController::class, 'show_Storage_Md']);
});

//mean that the products recievd successfully
