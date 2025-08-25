<?php

namespace App\Http\Controllers;


use Exception;
use Carbon\Carbon;
use App\Models\Bill;
use App\Models\type;
use App\Models\User;
use Faker\Core\Uuid;
use App\Models\Garage;
use App\Models\Employe;
use App\Models\Product;
use App\Models\Section;
use App\Models\Vehicle;
use App\Models\Favorite;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\Warehouse;
use App\Traits\CRUDTrait;
use App\Models\Import_jop;
use App\Models\Bill_Detail;
use Illuminate\Support\Str;
use App\Traits\LoadingTrait;
use Illuminate\Http\Request;
use App\Models\Storage_media;
use App\Traits\TransferTrait;
use GuzzleHttp\Handler\Proxy;
use App\Jobs\StoreVehiclesJob;
use App\Models\Specialization;
use App\Jobs\importing_op_prod;
use App\Models\Containers_type;
use App\Models\Transfer_detail;
use App\Traits\AlgorithmsTrait;
use Illuminate\Validation\Rule;
use App\Models\Import_operation;
use App\Models\Supplier_Details;
use App\Models\Supplier_Product;
use App\Traits\TransferTraitAeh;
use App\Jobs\importing_operation;
use App\Jobs\import_storage_media;
use App\Models\DistributionCenter;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Import_op_container;
use Illuminate\Support\Facades\Log;
use App\Models\Import_op_storage_md;
use App\Models\Posetions_on_section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Http\Resources\ProductResource;
use function PHPUnit\Framework\isEmpty;

use Illuminate\Support\Facades\Storage;
use App\Models\Import_operation_product;
use App\Notifications\Importing_success;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\storeProductRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\storeEmployeeRequest;
use App\Models\Distribution_center_Product;
use Illuminate\Validation\ValidationException;
use Illuminate\Notifications\DatabaseNotification;

use App\Events\Send_Notification;

class SuperAdmenController extends Controller
{
    use TransferTraitAeh;
    use CRUDTrait;
    use AlgorithmsTrait;
    use LoadingTrait;

    public function start_application(Request $request)
    {


        try {
            $validated_values = $request->validate([
                "name" => "required",
                "email" => "required|email",
                "password" => "required|min:8",
                "phone_number" => "required|max:10",
                "salary" => "required",
                "birth_day" => "date",
                "country" => "required",
                "start_time" => "required",
                "work_hours" => "required",
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }


        $admin = Employe::where(function ($query) use ($validated_values) {
            $query->where("email", $validated_values["email"])
                ->orWhere("phone_number", $validated_values["phone_number"]);
        })->first();

        if ($admin) {
            if ($admin->specialization->name == "super_admin") {
                return response()->json(["msg" => "your account already exist go to login"], 409);
            }
            return response()->json(["msg" => "you are not authorized"], 401);
        }

        $specialization = Specialization::where("name", "super_admin")->first();

        if ($specialization) {

            $admin = $specialization->employees;

            if (!$admin->isEmpty()) {
                return response()->json(["msg" => "you are not authorized"], 401);
            }
        }

        $specialization = Specialization::firstOrCreate(["name" => "super_admin"]);

        $requiredSpecs = [
            'warehouse_admin',
            'distribution_center_admin',
            "driver",
            "QA"

        ];



        foreach ($requiredSpecs as $spec) {
            Specialization::firstOrCreate(['name' => $spec]);
        }

        $validated_values["specialization_id"] = $specialization->id;


        $validated_values["password"] = Hash::make($validated_values['password']);
        try {
            $admin = Employe::create($validated_values);
        } catch (Exception $e) {
            return response()->json(["msg" => "error occurred while creating the admin", "error" => $e->getMessage()], 500);
        }

        $token = $this->create_token($admin);

        return response()->json(["msg" => "started succesfully!", "token" => $token], 201);
    }

    //first create a type to imprt the products type and warehouse type an etc
    public function create_new_specification(Request $request)
    {
        //specification is type or specialization
        $request->validate(["specification" => "required|in:type,Specialization"]);

        $validated_values = $request->validate(["name" => "required"]);

        $this->create_item("App\Models\\" . $request->specification, $validated_values);

        return response()->json(["msg" => "succesfuly adding"], 201);
    }
    public function create_new_type(Request $request)
    {
        try {
            $validated_values = $request->validate([
                "name" => "required"
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        $type = type::where("name", $validated_values["name"])->first();
        if ($type) {
            return response()->json(["msg" => "type already exist"], 409);
        }

        $type = type::create($validated_values);
        return response()->json(["msg" => "type added", "type_data" => $type], 201);
    }


    public function create_new_specialization(Request $request)
    {
        try {
            $validated_values = $request->validate([
                "name" => "required"
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        if (
            $request->name == "super_admin" || $request->name == "warehouse_admin"
            || $request->name == "distribution_center_admin" ||
            $request->name == "QA" || $request->name == "driver"
        ) {
            return response()->json(["msg" => "you want to renter basic specialization {$request->name} edit denied"], 403);
        }

        $spec = specialization::where("name", $validated_values["name"])->first();
        if ($spec) {
            return response()->json(["msg" => "specialization already exist"], 409);
        }

        $spec = specialization::create($validated_values);
        return response()->json(["msg" => "specialization added", "specialization_data" => $spec], 201);
    }
    public function show_all_warehouses()
    {
        try {




            $warehouses = Warehouse::with('type')
                ->get([
                    'id',
                    'name',
                    'location',
                    'latitude',
                    'longitude',
                    'status',
                    'num_sections',
                    'type_id'
                ]);

            if ($warehouses->isEmpty()) {
                return response()->json(["msg" => "you dont have warehouses yet"]);
            }
            return response()->json(["msg" => "here the warehouses", "warehouses" => $warehouses]);
        } catch (Exception $e) {
            return response()->json([

                'errors' => $e->getMessage(),
            ], 422);
        }
    }
    public function create_new_warehouse(Request $request)
    {
        try {
            $validated_values = $request->validate([
                "name" => "required|max:128",
                "location" => "required",
                "latitude" => "required|numeric",
                "longitude" => "required|numeric",
                "type_id" => "required|integer",
                "num_sections" => "required|integer"

            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        $warehouse = Warehouse::create($validated_values);
        return response()->json(["msg" => "warehouse added", "warehouse_data" => $warehouse], 201);
    }

    public function edit_warehouse(Request $request)
    {
        try {
            $validated_values = $request->validate([
                "warehouse_id" => "required",
                "name" => "max:128",
                "location" => "string",
                "latitude" => "numeric",
                "longitude" => "numeric",
                "type_id" => "integer",
                "num_sections" => "integer|min:1"

            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        $warehouse = Warehouse::find($validated_values["warehouse_id"]);
        if (!$warehouse) {
            return response()->json(["msg" => "warehouse not found"], 404);
        }
        unset($validated_values["warehouse_id"]);
        if (!empty($validated_values["type_id"])) {
            $type = type::find($validated_values["type_id"]);
            if (!$type) {
                return response()->json(["msg" => "type not found"], 404);
            }
            if ($validated_values["type_id"] != $warehouse->type_id) {
                $has_sections = $warehouse->sections()->exists();

                if ($has_sections) {
                    return response()->json(["msg" => "the waehouse has sections contain products of this type! cannot edit it"], 400);
                }
            }
        }
        if(!empty($validated_values["num_sections"])){
            $sections=$warehouse->sections()->where("status","!=","deleted")->count();
            if($validated_values["num_sections"]<$sections){
              return response()->json(["msg" => "the warehouse has sections more than the new number"], 400);
            }
        }
        try {
            $warehouse->update($validated_values);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()], 400);
        }
        return response()->json(["msg" => "edited succesfuly!"], 202);
    }

    public function delete_warehouse($warehouse_id)
    {try
        $warehouse = Warehouse::find($warehouse_id);
        if (!$warehouse) {
            return response()->json(["msg" => "warehouse not found"], 404);
        }
        $has_sections = $warehouse->sections()->exists();
        $has_employees = $warehouse->employees()->exists();
        $has_des_centers = $warehouse->distribution_centers()->exists();
        $has_garages=$warehouse->garages()->exists();
        if ($has_sections ||  $has_employees|| $has_garages || $has_des_centers) {
            return response()->json([
                "msg" => "the waehouse has realted data ! cannot delete it",
                "has_sections" => $has_sections,
                "has_employes" => $has_employees,
                "has_distribution_centers" => $has_des_centers
            ], 400);
        }
        $warehouse->delete($warehouse->id);
        return response()->json(["msg" => "deleted succesfuly!"], 202);
    }
    catch(Exception $e){
        return response()->json(["error" => $e->getMessage()], 400);
    }
}


    public function create_new_employe(Request $request)
    {

        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:4096'
        ]);
        try {


            $validated_values = $request->validate([
                "name" => "required",
                "email" => "required|email",
                "password" => "required|min:8",
                "phone_number" => "required|max:10",
                "specialization_id" => "required|integer",
                "salary" => "required",
                "birth_day" => "date",
                "country" => "required",
                "start_time" => "required",
                "work_hours" => "required",
                "workable_type" => "in:Warehouse,DistributionCenter",
                "workable_id" => "integer"
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        $checkEmploy = null;

        $checkEmploy = Employe::where("email", $validated_values["email"])->first();

        if ($checkEmploy) {

            return response()->json(["msg" => "you enter email already exists why?? !editing denied"], 403);
        }

        $checkEmploy = Employe::where("phone_number", $validated_values["phone_number"])->first();


        if ($checkEmploy) {

            return response()->json(["msg" => "you enter phone number already exists why?? !editing denied"], 403);
        }



        $password = Hash::make($validated_values["password"]);

        $validated_values['password'] = $password;

        if ($request->workable_type != null) {

            $validated_values['workable_type'] = "App\Models\\" . $request->workable_type;
        }
        if ($request->image != null) {

            $image = $request->file('image');
            $image_path = $image->store('Products', 'public');
            $validated_values["img_path"] = 'storage/' . $image_path;
        }
        try {
            $employe = Employe::create($validated_values);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()]);
        }

        $employe->specialization = $employe->specialization->name;
        return response()->json(["msg" => "succesfuly adding", "employe_data" => $employe], 201);
    }


    public function create_new_distribution_center(Request $request)
    {
        try {
            $validated_values = $request->validate([
                "name" => "required|max:128",
                "location" => "required",
                "latitude" => "required|numeric",
                "longitude" => "required|numeric",
                "warehouse_id" => "required|numeric",
                "num_sections" => "required|integer"
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        try {
            $validated_values["type_id"] = Warehouse::find($validated_values["warehouse_id"])->type_id;
            $center = DistributionCenter::create($validated_values);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()], 400);
        }
        return response()->json(["msg" => " distribution_center added!", "center_data" => $center], 201);
    }

    public function edit_distribution_center(Request $request)
    {

        try {
            $validated_values = $request->validate([
                "dis_center_id" => "required",
                "name" => "max:128",
                "location" => "string",
                "latitude" => "numeric",
                "longitude" => "numeric",
                "warehouse_id" => "numeric",
                "num_sections" => "integer|min:1"
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        $center = DistributionCenter::find($validated_values["dis_center_id"]);
        if (!$center) {
            return response()->json(["msg" => "distribution_center not found"], 404);
        }
        unset($validated_values["dis_center_id"]);

        if (!empty($validated_values["warehouse_id"])) {

            $warehouse = Warehouse::find($validated_values["warehouse_id"]);
            if (!$warehouse) {
                return response()->json(["msg" => "warehouse not found"], 404);
            }

            if ($warehouse->id != $center->warehouse_id) {
                $section_of_center = $center->sections()->first();

                if ($section_of_center) {
                    $product_in_dis = $section_of_center->product;
                    $type_id_of_product = $product_in_dis->type->id;
                    if ($type_id_of_product != $warehouse->type_id) {
                        return response()->json([
                            "msg" => "the warehouse type is not the same as the type of products in the distribution center! cannot move it to warehouse editing denied"
                        ], 400);
                    }
                }
            }
        }
        if(!empty($validated_values["num_sections"])){
            $sections=$warehouse->sections()->where("status","!=","deleted")->count();
            if($validated_values["num_sections"]<$sections){
              return response()->json(["msg" => "the warehouse has sections more than the new number"], 400);
            }
        }
        try {
            $center->update($validated_values);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()]);
        }
        return response()->json(["msg" => "edited succesfuly!"], 202);
    }


    public function delete_distribution_center($dest_id)
    {try{
        $dist_c = DistributionCenter::find($dest_id);
        if (!$dist_c) {
            return response()->json(["msg" => "the distributin center not found"], 404);
        }
        $has_sections = $dist_c->sections()->exists();
        $has_employees = $dist_c->employes()->exists();
        $has_garages=$dist_c->garages()->exists();
        if ($has_sections ||  $has_employees || $has_garages) {
            return response()->json([
                "msg" => "the distributin center has realted data ! cannot delete it",
                "has_sections" => $has_sections,
                "has_employes" => $has_employees
            ], 400);
        }
        $dist_c->delete($dist_c->id);
        return response()->json(["msg" => "deleted successfully!"], 202);
    }
    catch(Exception $e){
        
    }
}

    public function create_new_garage(Request $request)
    {
        try {
            $validated_values = $request->validate([
                "existable_type" => "string|in:Warehouse,DistributionCenter",
                "existable_id" => "integer",
                "size_of_vehicle" => "required|in:big,medium",
                "max_capacity" => "required|integer"

            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        $validated_values["existable_type"] = "App\\Models\\" . $validated_values["existable_type"];
        $garage = Garage::create($validated_values);

        return response()->json(["msg" => "created", "garage" => $garage], 201);
    }


    public function edit_garage(Request $request)
    {
        try {
            $validated_values = $request->validate([
                "garage_id" => "required",
                "existable_type" => "string|in:Warehouse,DistributionCenter",
                "existable_id" => "integer",
                "size_of_vehicle" => "in:big,medium",
                "max_capacity" => "integer"

            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        $garage = Garage::find($validated_values["garage_id"]);
        if (!$garage) {
            return response()->json(["msg" => "garage not found"], 404);
        }
        unset($validated_values["garage_id"]);
        if (!empty($validated_values["existable_type"])) {
            $validated_values["existable_type"] = "App\\Models\\" . $validated_values["existable_type"];
            $existable = $validated_values["existable_type"]::find($validated_values["existable_id"]);
            if (!$existable) {
                return response()->json(["msg" => "the place where you want transfer the garage not found"], 404);
            }
        }
        if ($validated_values["size_of_vehicle"]) {
            if ($garage->size_of_vehicle != $validated_values["size_of_vehicle"]) {
                $vehicles_in_garage = $garage->vehicles()->first();
                if ($vehicles_in_garage) {
                    return response()->json(["msg" => "the vehicle size in the garage is different from the new size! edit garage denied"], 400);
                }
            }
        }
        if ($validated_values["max_capacity"] < $garage->vehicles()->count()) {
            return response()->json(["msg" => " edit garage denied you want to reduce the capacity less than exists vehicles"], 400);
        }
        $garage->update($validated_values);
        return response()->json(["msg" => "edited successfully!"], 202);
    }
    public function delete_garage($garage_id)
    {
        $garage = Garage::find($garage_id);
        if (!$garage) {
            return response()->json(["msg" => "garage not found"], 404);
        }
        $has_vehicles = $garage->vehicles()->exists();
        if ($has_vehicles) {
            return response()->json([
                "msg" => "the garage has realted data! cannot delete it",
                "has_vehicles" => $has_vehicles
            ], 400);
        }
        try {
            $garage->delete($garage->id);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()], 409);
        }
        return response()->json(["msg" => "deleted successfully!"], 202);
    }

    public function show_garage_details($garage_id)
    {
        $garage = Garage::find($garage_id);
        if (!$garage) {
            return response()->json(["msg" => "garage not found"], 404);
        }
        return response()->json(["msg" => "garage details", "garage_data" => $garage], 202);
    }










    public function create_new_supplier(Request $request)
    {
        try {
            $validated_values = $request->validate([
                "comunication_way" => "required",
                "identifier" => "required",
                "country" => "required",
                "name" => "required"
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        $supplier = Supplier::where("identifier", $validated_values["identifier"])->get();
        if ($supplier->isNotEmpty()) {
            return response()->json(["msg" => "supplier already exist", "supplier_data" => $supplier], 400);
        }

        try {
            $supplier = Supplier::create($validated_values);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()]);
        }

        return response()->json(["msg" => "supplier added", "supplier_data" => $supplier], 201);
    }

    public function edit_supplier(Request $request)
    {
        try {
            $validated_values = $request->validate([
                "supplier_id" => "required",
                "comunication_way" => "string",
                "identifier" => "string",
                "country" => "string",
                "name" => "string"
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        $supplier = Supplier::find($validated_values["supplier_id"]);
        if (!$supplier) {
            return response()->json(["msg" => "supplier not found"], 404);
        }
        unset($validated_values["supplier_id"]);
        if (!empty($validated_values["identifier"])) {
            $supplier_check = Supplier::where("identifier", $validated_values["identifier"])->first();
            if ($supplier_check->id != $supplier->id) {
                return response()->json(["msg" => "identifier already exist on another supplier"], 400);
            }
        }
        try {
            $supplier->update($validated_values);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()], 409);
        }
        return response()->json(["msg" => "supplier edited"], 202);
    }

    public function delete_supplier($supplier_id)
    {
        $supplier = Supplier::find($supplier_id);
        if (!$supplier) {
            return response()->json(["msg" => "supplier not found"], 404);
        }
        $has_import_operations = $supplier->import_operations()->exists();
        if ($has_import_operations) {
            return response()->json([
                "msg" => "the supplier has realted data! cannot delete it",
                "has_import_operations" => $has_import_operations
            ], 400);
        }
        try {
            $supplier->delete($supplier->id);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()], 409);
        }
        return response()->json(["msg" => "deleted successfully!"], 202);
    }



    public function suppourt_new_product(Request $request)
    {
        try {
            try {
                $validated_values = $request->validate([
                    "name" => "required",
                    "description" => "required",
                    "import_cycle" => "string",
                    "type_id" => "required",
                    "actual_piece_price" => "required|numeric",
                    "unit" => "required",
                    "quantity" => "required",
                    "prod_sup_id" => "numeric",
                    "pr_max_delivery_time_by_days" => "required_with:prod_sup_id|numeric",
                    "lowest_temperature" => "numeric|nullable",
                    "highest_temperature" => "numeric|gte:lowest_temperature|nullable",
                    "lowest_humidity" => "numeric|nullable",
                    "highest_humidity" => "numeric|gte:lowest_humidity|nullable",
                    "lowest_light" => "numeric|nullable",
                    "highest_light" => "numeric|gte:lowest_light|nullable",
                    "lowest_pressure" => "numeric|nullable",
                    "highest_pressure" => "numeric|gte:lowest_pressure|nullable",
                    "lowest_ventilation" => "numeric|nullable",
                    "highest_ventilation" => "numeric|gte:lowest_ventilation|nullable",
                ]);

                $continer_values = request()->validate([
                    "name_container" => "required",
                    "capacity" => "required|numeric",

                ]);

                $storage_media_values = $request->validate([
                    "name_storage_media" => "required",
                    "num_floors" => "required|integer",
                    "num_classes" => "required|integer",
                    "num_positions_on_class" => "required|integer",
                    "sto_m_id" => "numeric",
                    "st_max_delivery_time_by_days" => "required_with:sto_m_id|numeric"
                ]);
            } catch (ValidationException $e) {

                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            $prod = Product::where("name", $validated_values["name"])->get();


            if ($prod->isNotEmpty()) {
                return response()->json(["msg" => "product already exist", "product_data" => $prod], 400);
            }
            try {
                $prod_sup = null;

                if (!empty($validated_values["prod_sup_id"])) {
                    $prod_sup["supplier_id"] = $validated_values["prod_sup_id"];
                    $prod_sup["max_delivery_time_by_days"] = $validated_values["pr_max_delivery_time_by_days"];
                }
                unset($validated_values["prod_sup_id"]);
                unset($validated_values["pr_max_delivery_time_by_days"]);
                $product = Product::create($validated_values);
                $continer_values["name"] = $continer_values["name_container"];
                unset($continer_values["name_container"]);
                $continer_values["product_id"] = $product->id;
                $container = Containers_type::create($continer_values);
                $storage_media_values["container_id"] = $container->id;
                $storage_media_values["name"] = $storage_media_values["name_storage_media"];
                unset($storage_media_values["name_storage_media"]);
                $sto_m_sup = null;
                if (!empty($storage_media_values["sto_m_id"])) {
                    $sto_m_sup["supplier_id"] = $storage_media_values["sto_m_id"];

                    $sto_m_sup["max_delivery_time_by_days"] = $storage_media_values["st_max_delivery_time_by_days"];
                }
                unset($storage_media_values["sto_m_id"]);
                unset($storage_media_values["st_max_delivery_time_by_days"]);
                $storage_media_values["product_id"] = $product->id;
                $storage_media = Storage_media::create($storage_media_values);
                if (!empty($prod_sup)) {
                    $product_supplier = Supplier::find($prod_sup["supplier_id"]);
                    if ($product_supplier) {
                        $prod_sup["suppliesable_type"] = "App\\Models\\Product";
                        $prod_sup["suppliesable_id"] = $product->id;
                        $product->supplier = $product_supplier;
                        Supplier_Details::create($prod_sup);
                    }
                }
                if (!empty($sto_m_sup)) {

                    $sto_m_sup_supplier = Supplier::find($sto_m_sup["supplier_id"]);
                    if ($sto_m_sup_supplier) {
                        $sto_m_sup["suppliesable_type"] = "App\\Models\\Storage_media";
                        $sto_m_sup["suppliesable_id"] = $storage_media->id;
                        $storage_media->supplier = $sto_m_sup_supplier;
                        Supplier_Details::create($sto_m_sup);
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(["msg" => $e->getMessage()], 500);
            }

            return response()->json([
                "msg" => "product and logestic things created succesfully!",
                "product_data" => $product,
                "container_data" => $container,
                "storage_media_data" => $storage_media
            ], 201);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 400);
        }
    }



    public function show_products()
    {

        $products = Product::all(); 
        
        foreach ($products as $product) {
            $product = $this->invintory_product_in_company($product);
        }
        return response()->json(["msg" => "sucessfull", "products" => $products], 202);
    }


    public function show_places_of_products($product_id)
    {
        $warehouses = [];
        $distribution_centers = [];
        $product = Product::find($product_id);
        $sections = $product->sections;
        foreach ($sections as $section) {
            $place = $section->existable;
            if ($section->existable_type == "App\\Models\\DistributionCenter") {
                $distribution_centers[$place->id] = $place;
            }

            if ($section->existable_type == "App\\Models\\Warehouse") {
                $warehouses[$place->id] = $place;
            }
        }
        return response()->json(["msg" => "here the places!", "warehouses" => $warehouses, "distribution_centers" => $distribution_centers]);
    }

    public function delete_product($product_id)
    {
        $product = Product::find($product_id);

        if (!$product) {
            return response()->json(['msg' => 'Product not found.'], 404);
        }


        $exists = Import_operation_product::where("product_id", $product_id)->exists();


        if ($exists) {
            return response()->json(["msg" => "you imported this product !! cannot delete"], 400);
        }

        if (Cache::has("import_op_products_keys")) {

            $import_op_products_keys = Cache::get("import_op_products_keys");

            foreach ($import_op_products_keys as $element) {

                $products = null;
                if (Cache::has($element["products_key"])) {

                    $products = Cache::get($element["products_key"]);
                }
                if ($products == null) {
                    continue;
                }

                foreach ($products as $may_imp_product) {

                    if ($may_imp_product["product_id"] == $product->id) {
                        return response()->json(["msg" => "you let this product in undeleted import operation !! cannot delete "], 400);
                    }
                }
            }
        }
        $storage_element = $product->storage_media;

        $exists = Import_op_storage_md::where("storage_media_id", $storage_element->id)->exists();

        if ($exists) {
            return response()->json(["msg" => "you import storage media for this product !! cannot delete"], 400);
        }

        if (Cache::has("import_op_storage_media_keys")) {
            "echo i am in has sto m in cashs";
            $import_op_storage_media = Cache::get("import_op_storage_media_keys");
            foreach ($import_op_storage_media as $element) {
                $storage_media = Cache::get($element["storage_media_key"]);
                if (!$storage_media) {
                    continue;
                }
                foreach ($storage_media as $storage_elements) {
                    if ($storage_element->id == $storage_elements["storage_media_id"]) {
                        return response()->json(["msg" => "you let storage element of this product in undeleted import operation !! cannot delete "], 400);
                    }
                }
            }
        }


        try {
            $storage_element->delete($storage_element->id);
            $product->delete($product_id);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e], 201);
        }
        return response()->json(["msg" => "deleted successfuly!"], 201);
    }


    public function edit_product(Request $request)
    {
        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json(['msg' => 'Product not found.'], 404);
        }

        $now = Carbon::now();
        $createdAt = Carbon::parse($product->created_at);
        $isOlderThan30Min = $createdAt->diffInMinutes($now) > 30;

        $alwaysUpdatable = [
            'quantity',
            'unit',
            'actual_piece_price',
            'import_cycle',
            'lowest_temperature',
            'highest_temperature',
            'lowest_humidity',
            'highest_humidity',
            'lowest_light',
            'highest_light',
            'lowest_pressure',
            'highest_pressure',
            'lowest_ventilation',
            'highest_ventilation',
            'description'
        ];

        if (!$isOlderThan30Min) {

            $alwaysUpdatable = array_merge($alwaysUpdatable, ['name', 'type_id']);
        } else {
            if ($request->hasAny(['name', 'type_id'])) {
                return response()->json([
                    'msg' => 'You can\'t edit name, or type after 30 minutes.'
                ], 403);
            }
        }


        $updateData = $request->only($alwaysUpdatable);


        if ($request->hasFile('img_path')) {

            if ($product->img_path && Storage::exists($product->img_path)) {
                Storage::delete($product->img_path);
            }


            $path = $request->file('img_path')->store('product_images', 'public');
            $updateData['img_path'] = $path;
        }

        $product->update($updateData);

        return response()->json([
            'msg' => 'Product updated successfully.',
            'product' => $product
        ],202);
    }




    public function create_new_section(Request $request)
    {
        try {
            $validated_values = $request->validate([
                "existable_type" => "required|in:DistributionCenter,Warehouse",
                "existable_id" => "required",
                "product_id" => "required",
                "num_floors" => "required|integer",
                "num_classes" => "required|integer",
                "num_positions_on_class" => "required|integer",
                "name" => "required"
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        $model = "App\\Models\\" . $validated_values["existable_type"];

        $place = $model::find($validated_values["existable_id"]);

        if (!$place) {
            return response()->json(["msg" => "the place which you want isnot exist"], 404);
        }
        try {
            $num_of_sections_on_place = $place->sections()->where("status", "!=", "deleted")->count();

            if ($num_of_sections_on_place == $place->num_sections) {
                return response()->json(["msg" => "the place is full"], 400);
            }
        } catch (\Exception $e) {
            return response()->json(["msg" => "Something went wrong"], 409);
        }
        $product = Product::find($validated_values["product_id"]);

        if (!$product) {
            return response()->json(["msg" => "the product which you want isnot exist"], 404);
        }
        if ($model == "App\\Models\\Warehouse") {
            if ($product->type->id != $place->type->id) {
                return response()->json(["msg" => "the type is not smothe"], 400);
            }
        }
        $validated_values["existable_type"] = $model;

        $section = Section::create($validated_values);

        $this->create_postions("App\\Models\\Posetions_on_section", $section, "section_id");

        return response()->json(["msg" => "section created succesfully", "section" => $section], 201);
    }

    public function edit_section(Request $request)
    {
        try {
            $validated_values = $request->validate([
                "section_id" => "required|integer",
                "name" => "string",
                "num_floors" => "integer",
                "num_classes" => "integer",
                "num_positions_on_class" => "integer"
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        };

        $section = Section::find($validated_values["section_id"]);
        if (!$section) {
            return response()->json(["msg" => "the section which you want is not exist"], 404);
        }
        $isOlderThan30Min = Carbon::now()->diffInMinutes($section->created_at) > 30;
        if ($isOlderThan30Min) {
            return response()->json([
                'msg' => 'You can\'t edit section after 30 minutes.'
            ], 403);
        }
        $has_storage_elements = $section->storage_elements()->exists();
        if ($has_storage_elements) {
            return response()->json(["msg" => "you have storage elements on this section!! cannot edit "], 400);
        }

        try {
            $section->posetions()->delete();
            unset($validated_values["section_id"]);
            $section->update($validated_values);

            $updated_section = Section::find($section->id);
            $this->create_postions("App\\Models\\Posetions_on_section", $updated_section, "section_id");
        } catch (\Exception $e) {
            return response()->json([
                'msg' => 'error while updating section',
                'errors' => $e,
            ], 500);
        }
        return response()->json(["msg" => "section updated succesfully", "section" => $updated_section], 202);
    }

    public function delete_section($section_id)
    {
        $section = Section::find($section_id);

        if (!$section) {
            return response()->json(["msg" => "the section which you want is not exist"], 404);
        }


        $has_storage_elements = $section->storage_elements()->exists();
        if ($has_storage_elements) {
            return response()->json(["msg" => "you have storage elements on this section!! cannot delete "], 400);
        }
        $section->status = "deleted";
        $section->posetions()->delete();
        $section->save();
        return response()->json(["msg" => "section deleted succesfully but i let it as a archiving to emprove the perormance"], 202);
    }
    public function create_new_imporet_op_storage_media(Request $request)
    {
        try {
            $keys = $request->validate([
                "import_operation_key" => "string",
                "storage_media_key" => "string"
            ]);


            $validated_values = $request->validate([
                "supplier_id" => "required|integer",
                "location" => "required",
                "latitude" => "required",
                "longitude" => "required"
            ]);

            $validated_items = $request->validate([
                'storage_media' => 'required|array|min:1',
                'storage_media.*.storage_media_id' => 'required|integer',
                'storage_media.*.quantity' => 'required|integer|min:1',
                'storage_media.*.section_id' => 'required|integer|min:1',
                'storage_media.*.position_id' => 'integer|min:1'
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }



        $storage_media = $validated_items["storage_media"];

        foreach ($storage_media as $storage_element) {
            $section = Section::find($storage_element["section_id"]);
            if ($section == null) {
                return response()->json(["msg" => "section not found"], 404);
            }
            $product = $section->product;
            $storage_ele = $product->storage_media;
            if ($storage_ele->id != $storage_element["storage_media_id"]) {
                return response()->json(["msg" => "storage media is not smoth with section"], 409);
            }


            if (!empty($storage_element["position_id"])) {
                $position = Posetions_on_section::find($storage_element["position_id"]);
                if ($position == null) {
                    return response()->json(["msg" => "position not found"], 404);
                }
                if ($position->section_id != $storage_element["section_id"]) {
                    return response()->json(["msg" => "position not in this section"], 422);
                }

                if ($storage_element["quantity"] > 1) {
                    return response()->json(["msg" => "you can't import more than one item on the same position"], 422);
                }
                if ($position->storage_media_id != null) {
                    return response()->json(["msg" => "position already has storage media"], 422);
                }
            }
        }

        $storage_media_key = null;
        $import_operation_key = null;


        if (empty($keys["storage_media_key"]) || empty($keys["import_operation_key"])) {

            $time = now();
            $storage_media_key = "storage_media" . $time;
            $import_operation_key = "import_operation" .  $time;
            $import_op_storage_media_keys = [];
            if (Cache::has("import_op_storage_media_keys")) {

                $import_op_storage_media_keys = Cache::get("import_op_storage_media_keys");
                Cache::forget("import_op_storage_media_keys");
            }

            $import_op_storage_media_keys[$import_operation_key] = [
                "import_operation_key" => $import_operation_key,
                "storage_media_key" => $storage_media_key,
            ];
            Cache::put("import_op_storage_media_keys", $import_op_storage_media_keys, now()->addMinutes(60));


            Cache::put($storage_media_key, $storage_media, now()->addMinutes(60));
            Cache::put($import_operation_key, $validated_values, now()->addMinutes(60));
        } else {
            Cache::forget($keys["storage_media_key"]);
            Cache::forget($keys["import_operation_key"]);


            $storage_media_key = $keys["storage_media_key"];
            $import_operation_key = $keys["import_operation_key"];

            $import_op_storage_media_keys = Cache::get("import_op_storage_media_keys");
            $import_op_storage_media_keys[$keys["import_operation_key"]]["import_operation_key"] = $keys["import_operation_key"];
            $import_op_storage_media_keys[$keys["import_operation_key"]]["storage_media_key"] = $keys["storage_media_key"];
            Cache::forget("import_op_storage_media_keys");

            Cache::put("import_op_storage_media_keys", $import_op_storage_media_keys, now()->addMinutes(60));

            Cache::put($storage_media_key, $storage_media, now()->addMinutes(60));
            Cache::put($import_operation_key, $validated_values, now()->addMinutes(60));
        }


        return response()->json([
            "msg" => "saved for one hour conferm it or edit or after it will be lost",
            "storage_media_key" => $storage_media_key,
            "import_operation_key" => $import_operation_key,
            "supplier_id" => $validated_values["supplier_id"],
            "location" => $validated_values["location"],
            "latitude" =>  $validated_values["latitude"],
            "longitude" =>  $validated_values["longitude"],
            "storage_media" => $validated_items["storage_media"]
        ], 201);
    }



    public function accept_import_op_storage_media(Request $request)
    {

        $storage_media = Cache::get($request->storage_media_key);
        $import_operation = Cache::get($request->import_operation_key);

        if (!$storage_media || !$import_operation) {
            return response()->json(["msg" => "already accepted or deleted"], 400);
        }
        $import_operation = Import_operation::create($import_operation);
        Cache::forget($request->import_operation_key);
        Cache::forget($request->storage_media_key);

        $job = new import_storage_media($import_operation->id, $storage_media);

        $jobId = Queue::later(now()->addMinutes(0), $job);
        return response()->json(["msg" => "storage_media under creating", "job_id" => $jobId, "storage_media" => $storage_media], 202);
    }


    public function show_latest_import_op_storage_media()
    {


        $import_op_storage_media_keys = Cache::get("import_op_storage_media_keys");

        $import_operations = [];
        $i = 1;
        if (!$import_op_storage_media_keys) {
            return response()->json(["no operation"]);
        }

        foreach ($import_op_storage_media_keys as $element) {
            $import_operation = Cache::get($element["import_operation_key"]);
            $storage_media = Cache::get($element["storage_media_key"]);
            if (!$import_operation || !$storage_media) {
                continue;
            }
            $element["supplier_id"] = $import_operation["supplier_id"];
            $element["supplier"] = Supplier::find($import_operation["supplier_id"]);
            $element["location"] = $import_operation["location"];
            $element["latitude"] = $import_operation["latitude"];
            $element["longitude"] = $import_operation["longitude"];
            $j = 1;
            foreach ($storage_media as $storage_element) {

                $section = Section::find($storage_element["section_id"]);
                if (!$section) {
                    $storage_media[$j]["section"] = "section deleted";
                    continue;
                }
                $section_empty_posetions = $section->posetions()->whereNull('storage_media_id')->get();
                $storage_media[$j]["empty_capacity"] = $section_empty_posetions->count();
                $section->existable;
                $storage_media[$j]["section"] = $section;

                $j++;
            }
            $element["storage_media"] = $storage_media;
            $import_operations[$i] = $element;
            $i++;
        }

        return response()->json(["import_operations" => $import_operations]);
    }





    public function create_new_import_operation_product(Request $request)
    {

        $keys = [
            "import_operation_key" => $request->import_operation_key,
            "products_key" => $request->products_key
        ];
        try {
            $validated_values = $request->validate([
                "supplier_id" => "required|integer",
                "location" => "required",
                "latitude" => "required",
                "longitude" => "required"
            ]);

            $validated_products = $request->validate([
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|integer|exists:products,id',
                'products.*.expiration' => 'required|date',
                'products.*.producted_in' => 'required|date',
                'products.*.price_unit' => 'required|numeric|min:0',
                'products.*.special_description' => 'string',
                'products.*.imported_load' => 'numeric|min:0',
                "products.*.distribution" => "required|array",
                "products.*.distribution.*.warehouse_id" => "required|integer",
                "products.*.distribution.*.load" => "required|min:1",
                "products.*.distribution.*.send_vehicles" => "required|boolean"

            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        $products_key = null;
        $import_operation_key = null;


        $products = $validated_products["products"];



        if (empty($keys["products_key"]) || empty($keys["import_operation_key"])) {

            $time = now();
            $products_key = "products_" . $time;
            $import_operation_key = "import_operation" . $time;
            $import_op_products_keys = [];
            if (Cache::has("import_op_products_keys")) {

                $import_op_products_keys = Cache::get("import_op_products_keys");
                Cache::forget("import_op_products_keys");
            }

            $import_op_products_keys[$import_operation_key] = [
                "import_operation_key" => $import_operation_key,
                "products_key" => $products_key,
            ];
            Cache::put("import_op_products_keys", $import_op_products_keys, now()->addMinutes(60));


            Cache::put($products_key, $products, now()->addMinutes(60));
            Cache::put($import_operation_key, $validated_values, now()->addMinutes(60));
        } else {
            Cache::forget($keys["products_key"]);
            Cache::forget($keys["import_operation_key"]);


            $products_key = $keys["products_key"];
            $import_operation_key = $keys["import_operation_key"];

            $import_op_products_keys = Cache::get("import_op_storage_media_keys");
            $import_op_products_keys[$keys["import_operation_key"]]["import_operation_key"] = $keys["import_operation_key"];
            $import_op_products_keys[$keys["import_operation_key"]]["products_key"] = $keys["products_key"];
            Cache::forget("import_op_storage_media_keys");

            Cache::put("import_op_storage_media_keys", $import_op_products_keys, now()->addMinutes(60));

            Cache::put($products_key, $products, now()->addMinutes(60));
            Cache::put($import_operation_key, $validated_values, now()->addMinutes(60));
        }


        return response()->json([
            "msg" => "saved for one hour conferm it or edit or after it will be lost",
            "products_key" => $products_key,
            "import_operation_key" => $import_operation_key,
            "supplier_id" => $validated_values["supplier_id"],
            "location" => $validated_values["location"],
            "latitude" =>  $validated_values["latitude"],
            "longitude" =>  $validated_values["longitude"],
            "products" => $products
        ], 201);
    }

    public function accept_import_op_products(Request $request)
    {

        $products = Cache::get($request->products_key);
        $import_operation = Cache::get($request->import_operation_key);
        if (!$products || !$import_operation) {
            return response()->json(["msg" => "already accepted or deleted"], 400);
        }
        $import_operation = Import_operation::create($import_operation);
        Cache::forget($request->import_operation_key);
        Cache::forget($request->storage_media_key);

        importing_op_prod::dispatch($import_operation, $products);

        return response()->json(["msg" => "roducts under distributiun"], 202);
    }


    public function reject_import_op(Request $request)
    {
        $elements = Cache::get($request->key);
        $import_operation = Cache::get($request->import_operation_key);
        if (!$elements || !$import_operation) {
            return response()->json(["msg" => "already accepted or deleted"], 400);
        }
        Cache::forget($request->import_operation_key);
        Cache::forget($request->key);

        return response()->json(["msg" => "import opertation rejected successfuly"], 202);
    }

    public function show_latest_import_op_products()
    {
        $import_op_products_keys = Cache::get("import_op_products_keys");
        $import_operations = [];

        $i = 0;
        if (!$import_op_products_keys) {
            return response()->json(["no operation"]);
        }

        foreach ($import_op_products_keys as $element) {

            $import_operation = Cache::get($element["import_operation_key"]);
            $products = Cache::get($element["products_key"]);
            if (!$import_operation || !$products) {

                continue;
            }
            $element["supplier_id"] = $import_operation["supplier_id"];
            $element["supplier"] = Supplier::find($import_operation["supplier_id"]);
            $element["location"] = $import_operation["location"];
            $element["latitude"] = $import_operation["latitude"];
            $element["longitude"] = $import_operation["longitude"];

            $element["products"] = $products;
            $i = 0;
            foreach ($element["products"] as $product) {
                $element["products"][$i]["product"] = Product::find($product["product_id"]);
                $j = 0;

                foreach ($element["products"][$i]["distribution"] as  $dist) {

                    $warehouse = Warehouse::find($dist["warehouse_id"]);


                    $dist["warehouse"] = $this->calcute_areas_on_place_for_a_specific_product($warehouse, $element["products"][$i]["product_id"]);
                    $element["products"][$i]["distribution"][$j]["warehouse"] = $dist["warehouse"];
                    $j++;
                }
                $i++;
            }


            $import_operations[$i] = $element;
        }


        return response()->json(["import_operations" => $import_operations]);
    }

    public function create_import_op_vehicles(Request $request)
    {
        $keys = [
            "import_operation_key" => $request->import_operation_key,
            "vehicles_key" => $request->vehicles_key
        ];
        try {
            $validated_values = $request->validate([
                "supplier_id" => "required|integer",
                "location" => "required",
                "latitude" => "required",
                "longitude" => "required"
            ]);

            $validated_vehicles = $request->validate([
                'vehicles' => 'required|array|min:1',

                'vehicles.*.name' => 'required|string',
                'vehicles.*.expiration' => 'required|date',
                'vehicles.*.producted_in' => 'required|date',
                'vehicles.*.readiness' => 'required|numeric|min:0',
                'vehicles.*.location' => 'string',
                'vehicles.*.latitude' => 'numeric',
                'vehicles.*.longitude' => 'numeric',
                'vehicles.*.size_of_vehicle' => 'required|in:big,medium',
                'vehicles.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,bmp|max:4096',
                'vehicles.*.capacity' => 'required|integer',
                'vehicles.*.product_id' => 'required|integer|exists:products,id',
                'vehicles.*.place_type' => 'required|in:Warehouse,DistributionCenter',
                'vehicles.*.place_id' => 'required|integer'
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        $vehicles = $validated_vehicles["vehicles"];
        $vehicles_key = null;
        $import_operation_key = null;

        if (empty($keys["vehicles_key"]) || empty($keys["import_operation_key"])) {

            $time = now()->timestamp;
            $vehicles_key = "vehicles_" . $time;
            $import_operation_key = "import_operation_" . $time;

            $import_op_vehicles_keys = [];

            if (Cache::has("import_op_vehicles_keys")) {
                $import_op_vehicles_keys = Cache::get("import_op_vehicles_keys");
                Cache::forget("import_op_vehicles_keys");
            }

            $import_op_vehicles_keys[$import_operation_key] = [
                "import_operation_key" => $import_operation_key,
                "vehicles_key" => $vehicles_key,
            ];

            Cache::put("import_op_vehicles_keys", $import_op_vehicles_keys, now()->addMinutes(60));
            Cache::put($vehicles_key, $vehicles, now()->addMinutes(60));
            Cache::put($import_operation_key, $validated_values, now()->addMinutes(60));
        } else {

            $vehicles_key = $keys["vehicles_key"];
            $import_operation_key = $keys["import_operation_key"];

            Cache::forget($vehicles_key);
            Cache::forget($import_operation_key);

            Cache::put($vehicles_key, $vehicles, now()->addMinutes(60));
            Cache::put($import_operation_key, $validated_values, now()->addMinutes(60));
        }



        return response()->json([
            "msg" => "Saved for one hour. Confirm or edit it before it expires.",
            "vehicles_key" => $vehicles_key,
            "import_operation_key" => $import_operation_key,
            "supplier_id" => $validated_values["supplier_id"],
            "location" => $validated_values["location"],
            "latitude" => $validated_values["latitude"],
            "longitude" => $validated_values["longitude"],
            "vehicles" => $vehicles
        ], 201);
    }

    public function accept_import_op_vehicles(Request $request)
    {

        $vehicles = Cache::get($request->vehicles_key);
        $import_operation = Cache::get($request->import_operation_key);
        if (!$vehicles || !$import_operation) {
            return response()->json(["msg" => "already accepted or deleted"], 400);
        }
        $import_operation = Import_operation::create($import_operation);
        Cache::forget($request->import_operation_key);
        Cache::forget($request->vehicles_key);
        StoreVehiclesJob::dispatch($vehicles, $import_operation->id, $import_operation->location, $import_operation->longitude, $import_operation->latitude);

        return response()->json(["msg" => "vehicles under creating"], 202);
    }

    public function show_live_import_op_vehicles()
    {

        $import_op_vehicles_keys = Cache::get("import_op_vehicles_keys");
        if (!$import_op_vehicles_keys) {
            return response()->json(["msg"=>"no operation"]);
        }
        $import_operations = [];
        foreach ($import_op_vehicles_keys as $element) {
            $import_operation = Cache::get($element["import_operation_key"]);
            $vehicles = Cache::get($element["vehicles_key"]);
            if (!$import_operation || !$vehicles) {
                continue;
            }

            $element["supplier_id"] = $import_operation["supplier_id"];
            $element["supplier"] = Supplier::find($import_operation["supplier_id"]);
            $element["location"] = $import_operation["location"];
            $element["latitude"] = $import_operation["latitude"];
            $element["longitude"] = $import_operation["longitude"];
           

            foreach ($vehicles as $vehicle) {
                $model = "App\\Models\\" . $vehicle["place_type"];
                $vehicle["place"] = $model::find($vehicle["place_id"]);
            }
             $element["vehicles"] = $vehicles;
            $import_operations[] = $element;
        }

        return response()->json(["msg" => "here the live import op vehicles", "imports" => $import_operations], 202);
        try {
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 400);
        }
    }



    public function show_latest_import_op_vehicles()
    {
        $import_op_vehicles_keys = Cache::get("import_op_vehicles_keys");

        $import_operations = [];
        $i = 0;
        if ($import_op_vehicles_keys) {

            foreach ($import_op_vehicles_keys as $key => $value) {

                $import_operation = Cache::get($value["import_operation_key"]);

                $vehicles = Cache::get($value["vehicles_key"]);
                if (!$import_operation || !$vehicles) {
                    continue;
                }

                $value["supplier_id"] = $import_operation["supplier_id"];
                $value["location"] = $import_operation["location"];
                $value["latitude"] = $import_operation["latitude"];
                $value["longitude"] = $import_operation["longitude"];
                $value["supplier"] = Supplier::find($import_operation["supplier_id"]);
                $j = 1;
                foreach ($vehicles as $vehicle) {

                    $model = "App\\Models\\" . $vehicle["place_type"];

                    $place = $model::find($vehicle["place_id"]);

                    $place = $this->calculate_areas_of_vehicles($place);
                    //return $place;
                    $vehicles[$j]["place"] = $place;
                    $j++;
                }
                $value["vehicles"] = $vehicles;
                $import_operations[$i] = $value;
                $i++;
            }
        }
        return response()->json(["msg" => "here the latest import_operations", "import_operations" => $import_operations]);
    }
    public function add_new_supplies_to_supplier(Request $request)
    {
        try {
            $validated_values = $request->validate([
                "supplier_id" => "required",
                "suppliesable_type" => "required|in:Product,Storage_media",
                "suppliesable_id" => "required",
                "max_delivery_time_by_days" => "required|numeric"

            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        $supplier = Supplier::find($validated_values["supplier_id"]);

        if (!$supplier) {
            return response()->json(["msg" => "supplier is not exist!"], 404);
        }
        $model = "App\\Models\\" . $validated_values["suppliesable_type"];

        $supplies = $model::find($validated_values["suppliesable_id"]);

        if (!$supplies) {

            return response()->json(["msg" => "supplies is not exist!"], 404);
        }
        $validated_values["suppliesable_type"] = $model;
        $supplies_info = Supplier_Details::where("supplier_id", $validated_values["supplier_id"])->where("suppliesable_type", $validated_values["suppliesable_type"])->where("suppliesable_id", $validated_values["suppliesable_id"])->get();

        if (!$supplies_info->isEmpty()) {

            return response()->json(["msg" => "supplier already suport that!??"], 400);
        }



        Supplier_Details::create($validated_values);

        return response()->json(["msg" => "now the supplier support that", "supplies" => $supplies], 201);
    }

    public function delete_supplies_from_supplier($supplies_id)
    {
        $supplies = Supplier_Details::find($supplies_id);
        if (!$supplies) {
            return response()->json(["msg" => "supplies is not exist"], 404);
        }
        $supplies->delete($supplies->id);
        return response()->json(["msg" => "supplies deleted successfully"]);
    }
    public function show_suppliers()
    {
        $suppliers = Supplier::all();
        return response()->json(["suppliers" => $suppliers]);
    }

    public function show_products_of_supplier($id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json(["msg" => "supplier is npt exist"], 400);
        }

        $supplier_products = $supplier->supplier_products;
        foreach ($supplier_products as $product) {
            $sections = $product->sections;
            $avilable_area = 0;
            $max_area = 0;
            foreach ($sections as $section) {
                $areas = $this->calculate_areas($section);
                $avilable_area += $areas["avilable_area"];
                $max_area += $areas["max_capacity"];
            }
            $product->avilable_area = $avilable_area;
            $product->max_area = $max_area;
        }

        return response()->json(["supplier_products" => $supplier_products], 202);
    }

    public function import_archive_for_supplier($sup_id)
    {
        try {
            $supplier = Supplier::find($sup_id);
            if (!$supplier) {
                return response()->json(["msg" => "supplier is npt exist"], 400);
            }
            $import_operations = $supplier->import_operations;
            if ($import_operations->isEmpty()) {
                return response()->json(["msg" => "supplier has no import operations"], 400);
            }
            return response()->json(["import_operations" => $import_operations], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 400);
        }
    }

    public function show_import_opreation_content($imp_op_id)
    {
        try {
            $import_operation = Import_Operation::find($imp_op_id);
            if (!$import_operation) {
                return response()->json(["msg" => "import operation not exist"], 400);
            }
            $vehicles = $import_operation->vehicles;
            unset($import_operation->vehicles);
            $storage_media = $import_operation->storage_media()->with("parent_storage_media")->get();
            $products = $import_operation->cargos()->with("parent_product")->get();
            return response()->json(["import_operation" => $import_operation, "vehicles" => $vehicles, "storage_media" => $storage_media, "products" => $products], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 400);
        }
    }
    public function show_warehouses_of_product($id)
    {
        $product = Product::find($id);

        $type = $product->type;


        $warehouses = $type->warehouses;


        $warehouses_with_details = [];
        $i = 1;
        foreach ($warehouses as $warehouse) {

            $warehouse = $this->calcute_areas_on_place_for_a_specific_product($warehouse, $id);
            $warehouse = $this->calculate_ready_vehiscles($warehouse, $product);
            $warehouses_with_details[$i] = $warehouse;

            $i++;
        }
        return response()->json(["msg" => "here the warehouses", "warehouses" => $warehouses_with_details]);
    }


    public function show_storage_media_of_supplier($id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json(["msg" => "supplier is npt exist"], 400);
        }

        $supplier_storage_media = $supplier->supplier_storage_media;


        return response()->json(["supplier_storage_media" => $supplier_storage_media], 202);
    }


    public function show_supplier_of_storage_media($storage_media_id)
    {
        $storage_element = Storage_media::find($storage_media_id);

        if ($storage_element) {
            $suppliers = $storage_element->supplier;
            return response()->json(["msg" => "here is the suppliers!", "suppliers" => $suppliers], 202);
        }
        return response()->json(["msg" => "storage_element not found!"], 404);
    }


    public function show_suppliers_of_product($id)
    {

        $product = Product::find($id);
        if (!$product) {
            return response()->json(["msg" => "the product not exist"], 404);
        }
        $supplier_of_product = $product->supplier;
        return response()->json(["suppliers_of_product" => $supplier_of_product]);
    }

    public function edit_storage_media(Request $request)
    {
        try {
            $validated_values = $request->validate([
                "storage_media_id" => "required",
                "name" => "string",
                "num_floors" => "integer",
                "num_classes" => "integer",
                "num_positions_on_class" => "integer",
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        $storage_element = Storage_media::find($validated_values["storage_media_id"]);;
        if (!$storage_element) {
            return response()->json(["msg" => "storage_media not found"], 404);
        }

        $now = Carbon::now();
        $createdAt = Carbon::parse($storage_element->created_at);
        $isOlderThan30Min = $createdAt->diffInMinutes($now) > 30;


        if (!$isOlderThan30Min) {
            unset($validated_values["storage_media_id"]);
            try {
                $storage_element->update($validated_values);
            } catch (\Exception $e) {
                return response()->json([
                    'msg' => 'editing fild',
                    'errors' => $e,
                ], 422);
            }
            return response()->json([
                'msg' => 'edited seccessfully'
            ], 201);
        } else {

            return response()->json([
                'msg' => 'You can\'t edit after 30 minutes.'
            ], 403);
        }
    }

    public function edit_continer(Request $request)
    {
        try {

            $validated_values = $request->validate([
                "container_id" => "required",
                "capacity" => "numeric",
                "name" => "string"

            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        $continer = Containers_type::find($validated_values["container_id"]);;
        if (!$continer) {
            return response()->json(["msg" => "container not found"], 404);
        }

        $now = Carbon::now();
        $createdAt = Carbon::parse($continer->created_at);
        $isOlderThan30Min = $createdAt->diffInMinutes($now) > 30;


        if (!$isOlderThan30Min) {
            unset($validated_values["container_id"]);
            try {
                $continer->update($validated_values);
            } catch (\Exception $e) {
                return response()->json([
                    'msg' => 'editing fild',
                    'errors' => $e,
                ], 422);
            }
            return response()->json([
                'msg' => 'edited seccessfully'
            ], 201);
        } else {

            return response()->json([
                'msg' => 'You can\'t edit after 30 minutes.'
            ], 403);
        }
    }


    public function show_warehouse_of_storage_media($storage_media_id)
    {
        $storage_media = Storage_media::find($storage_media_id);
        if (!$storage_media) {
            return response()->json(["msg" => "Storage_media not not found"], 404);
        }
        $product = $storage_media->product;

        return $this->show_warehouses_of_product($product->id);
    }

    public function show_sections_of_storage_media_on_place($storage_media_id, $place_type,$place_id)
    { 
        try{
          $model="App\\Models\\".$place_type;
          
          $place=$model::find($place_id);
          if(!$place){
              return response()->json(["msg"=>"place not found"],404);
          }
        $storage_media = Storage_media::find($storage_media_id);
        if (!$storage_media) {
            return response()->json(["msg" => "storage_media not found"], 404);
        }

        $product = $storage_media->product;

        
        
        $sections = $place->sections()
            ->where('product_id', $product->id)->where("status", "!=", "deleted")
            ->select([
                'id',
                'name',
                'product_id',
                'num_floors',
                'num_classes',
                'num_positions_on_class',
                'average',
                'variance'
            ])
            ->get();

        if ($sections->isEmpty()) {
            return response()->json(["msg" => "sections not found"], 404);
        }

        foreach ($sections as $section) {

            $areas = $this->calculate_areas($section);

            $section->storage_media_avilable_area = $areas["storage_media_avilable_area"];
            $section->storage_media_max_area = $areas["storage_media_max_area"];
        }
        return response()->json(["msg" => "here the sections", "sections" => $sections], 202);
    }
    catch(Exception $e){
       return response()->json(["msg"=>$e->getMessage()],404);
    }
}

    public function show_all_types()
    {
        $types = type::all();
        return response()->json(["msg" => "here the types!", "types" => $types]);
    }
    public function edit_type(Request $request)
    {
        try {

            $validated_values = $request->validate([
                "type_id" => "required",
                "name" => "string"

            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        $type = type::find($validated_values["type_id"]);
        if (!$type) {
            return response()->json(["msg" => "type not found"], 404);
        }
        $now = Carbon::now();
        $createdAt = Carbon::parse($type->created_at);
        $isOlderThan30Min = $createdAt->diffInMinutes($now) > 30;


        if (!$isOlderThan30Min) {
            unset($validated_values["type_id"]);
            try {
                $type->update($validated_values);
            } catch (\Exception $e) {
                return response()->json([
                    'msg' => 'editing fild',
                    'errors' => $e,
                ], 422);
            }
            return response()->json([
                'msg' => 'edited seccessfully'
            ], 201);
        } else {

            return response()->json([
                'msg' => 'You can\'t edit after 30 minutes.'
            ], 403);
        }
    }


    public function delete_type($type_id)
    {
        try {
            $type = type::find($type_id);
            if (!$type) {
                return response()->json(["msg" => "type not found"], 404);
            }
            $warehouse_of_type = $type->warehouses;

            $products_of_type = $type->products;


            if (!$warehouse_of_type->isEmpty() || !$products_of_type->isEmpty()) {
                return response()->json([
                    "msg" => "the type hase a data",
                    "warehouses" => $warehouse_of_type,
                    "products" => $products_of_type,

                ], 400);
            }
            $type->delete();
            return response()->json(["msg" => "type deleted succesfuly!"], 202);
        } catch (\Exception $e) {

            return response()->json(["msg" => $e->getMessage()], 404);
        }
    }


    public function show_warehouse_of_type($type_id)
    {
        $type = type::find($type_id);
        if (!$type) {
            return response()->json(["msg" => "type not found"], 404);
        }
        $warehouses = $type->warehouses;
        if ($warehouses->isEmpty()) {
            return response()->json(["msg" => "warehouses of this type not found"], 404);
        }
        return response()->json(["msg" => "here the warehouses", "warehouses_of_type" => $warehouses], 202);
    }
    public function show_products_of_type($type_id)
    {
        $type = type::find($type_id);
        if (!$type) {
            return response()->json(["msg" => "type not found"], 404);
        }
        $products = $type->products;
        if ($products->isEmpty()) {
            return response()->json(["msg" => "products of this type not found"], 404);
        }
        return response()->json(["msg" => "here the products", "products_of_type" => $products], 202);
    }

    public function show_all_specializations()
    {
        $sprcializations = Specialization::all();
        if ($sprcializations->isEmpty()) {
            return response()->json(["msg" => "you dont have specializations yet !!"], 404);
        }
        return response()->json(["msg" => "here the specializations", "specializations" => $sprcializations], 202);
    }


    public function delete_Specialization($spec_id)

    {
        try {
            $spec = Specialization::find($spec_id);
            if (!$spec) {
                return response()->json(["msg" => "specialization not found"], 404);
            }
            if (
                $spec->name == "super_admin" || $spec->name == "warehouse_admin"
                || $spec->name == "distribution_center_admin" ||
                $spec->name == "QA" || $spec->name == "driver"
            ) {
                return response()->json(["msg" => "you want to delete basic specialization {$spec->name} delete denied"], 403);
            }
            $employees_of_spec = $spec->employees;
            if (!$employees_of_spec->isEmpty()) {
                return response()->json(["msg" => "the specialization has emplyees you cannot delete it", "employes" => $employees_of_spec], 403);
            }
            $spec->delete($spec->id);
            return response()->json(["msg" => "deleted succesfuly!"], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 404);
        }
    }
    public function edit_Specialization(Request $request)
    {
        try {

            $validated_values = $request->validate([
                "spec_id" => "required",
                "name" => "string"

            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        $spec = Specialization::find($validated_values["spec_id"]);
        if (!$spec) {
            return response()->json(["msg" => "the specialization not found"], 404);
        }
        if (
            $spec->name == "super_admin" || $spec->name == "warehouse_admin"
            || $spec->name == "distribution_center_admin" ||
            $spec->name == "QA" || $spec->name == "driver"
        ) {
            return response()->json(["msg" => "you want to edit basic specialization {$spec->name} edit denied"], 403);
        }
        if (
            $request->name == "super_admin" || $request->name == "warehouse_admin"
            || $request->name == "distribution_center_admin" ||
            $request->name == "QA" || $request->name == "driver"
        ) {
            return response()->json(["msg" => "you want to renter basic specialization {$request->name} edit denied"], 403);
        }
        $now = Carbon::now();
        $createdAt = Carbon::parse($spec->created_at);
        $isOlderThan30Min = $createdAt->diffInMinutes($now) > 30;


        if (!$isOlderThan30Min) {
            unset($validated_values["spec_id"]);
            try {
                $spec->update($validated_values);
            } catch (\Exception $e) {
                return response()->json([
                    'msg' => 'editing fild',
                    'errors' => $e,
                ], 422);
            }
            return response()->json([
                'msg' => 'edited seccessfully'
            ], 201);
        } else {

            return response()->json([
                'msg' => 'You can\'t edit after 30 minutes.'
            ], 403);
        }
    }

    public function show_employees_of_spec($spec_id)
    {
        $spec = Specialization::find($spec_id);

        if (!$spec) {

            return response()->json(["msg" => "specialization not found"], 404);
        }
        $employees = $spec->employees;
        if ($employees->isEmpty()) {
            return response()->json(["msg" => "emplyees of this specialization not found"], 404);
        }
        return response()->json(["msg" => "here the employes", "employees" => $employees], 202);
    }
    public function show_all_employees()
    {
        $employees = Specialization::whereHas('employees')->with('employees')->where("name", "!=", "super_admin")->get();
        return response()->json(["msg" => "here the employees", "employees" => $employees], 202);
    }


    public function cancel_employe($emp_id)
    {
        $employe = Employe::find($emp_id);
        if (!$employe) {
            return response()->json(["msg" => "emplye not found"], 404);
        }
        $specialization = $employe->specialization;
        if ($specialization->name == "super_admin") {
            return response()->json(["msg" => "cannot cancel a super admin! cancel denied "], 403);
        }
        $employe->delete($employe);
        return response()->json(["msg" => " canceled succesfully! "], 202);
    }


    public function edit_employe(Request $request)
    {
        try {
            try {

                $validated_values = $request->validate([
                    'employe_id' => "required",
                    "name" => "string",
                    "email" => "email",
                    "password" => "min:8",
                    "phone_number" => "max:10",
                    "specialization_id" => "integer",
                    "salary" => "numeric",
                    "birth_day" => "date",
                    "country" => "string",
                    "start_time" => "date_format:H:i",
                    "work_hours" => "numeric",
                    "workable_type" => "in:Warehouse,DistributionCenter",
                    "workable_id" => "integer"
                ]);
            } catch (ValidationException $e) {

                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            $employe = Employe::find($validated_values["employe_id"]);

            if (!$employe) {
                return response()->json(["msg" => "emplye not found"], 404);
            }

            unset($validated_values["employe_id"]);
            $specialization = Specialization::find($validated_values["specialization_id"]);
            if (!empty($validated_values["specialization_id"])) {
                if ($specialization->name == "super_admin") {
                    return response()->json(["msg" => "you cannot set specialization to super admin !editing denied"], 403);
                }
            }




            if (!empty($validated_values["password"])) {
                $validated_values["password"] = Hash::make($validated_values["password"]);
            }
            try {
                $employe = $employe->update($validated_values);
            } catch (Exception $e) {
                return response()->json(["error" => $e->getMessage()]);
            }
            return response()->json(["msg" => "editing succesfully!"], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 404);
        }
    }
    public function try_choise_trucks($warehouse_id, $import_operation_id)
    {
        DB::beginTransaction();
        $warehouse = Warehouse::find($warehouse_id);
        if (!$warehouse) {
            return response()->json(["msg" => "warehouse not found"], 404);
        }

        $user = User::find($import_operation_id);


        $continers = Import_op_container::where("id", "<=", 89)->where("id", ">=", 86)->get();

        try {
            $truks_continers = $this->resive_transfers($warehouse, $user, $continers);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 404);
        }
        return response()->json(["trucks" => $truks_continers, "cons" => $continers], 202);
    }
    public function show_storage_media_of_product($product_id)
    {
        try {
            $product = Product::find($product_id);
            if (!$product) {
                return response()->json(["msg" => "product not found"], 404);
            }
            $storage_media = $product->storage_media;
            if (!$storage_media) {
                return response()->json(["msg" => "storage_media not found"], 404);
            } else {
                return response()->json(["storage_media" => $storage_media], 202);
            }
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 404);
        }
    }
    public function show_container_of_product($product_id)
    {
        $product = Product::find($product_id);
        if (!$product) {
            return response()->json(["msg" => "product not found"], 404);
        }
        $container = $product->container;
        if (!$container) {
            return response()->json(["msg" => "container not found"], 404);
        } else {
            return response()->json(["container" => $container], 202);
        }
    }

    public function resive_notification(Request $request)
    {
        DB::beginTransaction();
        try {
            $employe = $request->employe;
            $uuid = (string) Str::uuid();
            $notification = new Importing_success("product");

            $notify = DatabaseNotification::create([
                'id' => $uuid,
                'type' => get_class($notification),
                'notifiable_type' => get_class($employe),
                'notifiable_id' => $employe->id,
                'data' => $notification->toArray($employe),
                'read_at' => null,
            ]);

            event(new Send_Notification($employe, $notification));
            DB::commit();
            return response()->json(["msg" => "notification sent", "notifi" => $notify], 202);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(["msg" => $e->getMessage()], 404);
        }
    }
}
