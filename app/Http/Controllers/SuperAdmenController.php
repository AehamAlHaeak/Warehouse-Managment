<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\type;
use App\Jobs\importing_operation;
use App\Jobs\importing_op_prod;
use App\Models\User;
use App\Models\Posetions_on_section;
use App\Models\Import_op_storage_md;
use App\Models\Garage;
use App\Models\Employe;
use App\Models\Product;
use App\Jobs\import_storage_media;
use App\Models\Vehicle;
use App\Models\Favorite;
use App\Models\Supplier;

use App\Models\Transfer;
use App\Models\Warehouse;
use App\Traits\CRUDTrait;
use App\Models\Bill_Detail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Specialization;
use App\Models\Supplier_Product;
use App\Models\Transfer_Vehicle;
use App\Models\Werehouse_Product;
use App\Models\DistributionCenter;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\LoadingTrait;
use Illuminate\Support\Facades\Auth;
use App\Models\Import_jop_product;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\storeProductRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\storeEmployeeRequest;
use App\Models\Containers_type;
use App\Models\Distribution_center_Product;
use App\Models\Import_jop;
use App\Models\Import_operation;
use App\Models\Section;
use App\Models\Storage_media;
use App\Traits\AlgorithmsTrait;
use App\Traits\TransferTrait;
use GuzzleHttp\Handler\Proxy;
use Symfony\Component\HttpKernel\HttpCache\ResponseCacheStrategy;

class SuperAdmenController extends Controller
{
    use TransferTrait;
    use CRUDTrait;
    use AlgorithmsTrait;
    use LoadingTrait;
    //first create a type to imprt the products type and warehouse type an etc
    public function create_new_specification(Request $request)
    {
        //specification is type or specialization
        $request->validate(["specification" => "required|in:type,Specialization"]);

        $validated_values = $request->validate(["name" => "required"]);

        $this->create_item("App\Models\\" . $request->specification, $validated_values);

        return response()->json(["msg" => "succesfuly adding"], 201);
    }


    public function create_new_warehouse(Request $request)
    {

        $validated_values = $request->validate([
            "name" => "required|max:128",
            "location" => "required",
            "latitude" => "required|numeric",
            "longitude" => "required|numeric",
            "type_id" => "required|integer",
            "num_sections" => "required|integer"

        ]);


        $warehouse = Warehouse::create($validated_values);
        return response()->json(["msg" => "warehouse added", "warehouse_data" => $warehouse], 201);
    }


    public function create_new_employe(storeEmployeeRequest $request)
    {
        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:4096'
        ]);
        $validated_values = $request->validated();

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
        $employe = Employe::create($validated_values);
        $employe->specialization = $employe->specialization->name;
        return response()->json(["msg" => "succesfuly adding", "employe_data" => $employe], 201);
    }


    public function create_new_distribution_center(Request $request)
    {

        $validated_values = $request->validate([
            "name" => "required|max:128",
            "location" => "required",
            "latitude" => "required|numeric",
            "longitude" => "required|numeric",
            "warehouse_id" => "required|numeric",
            "type_id" => "required|integer",
            "num_sections" => "required|integer"
        ]);


        $center = DistributionCenter::create($validated_values);
        return response()->json(["msg" => " distribution_center added!", "center_data" => $center], 201);
    }










    public function create_new_vehicle(Request $request)
    {

        $validated_values = $request->validate([
            "name" => "required",
            "expiration" => "required|date",
            "producted_in" => "required|date",
            "readiness" => "required|numeric|min:0|max:1",
            "max_load" => "required|numeric|min:1000",
            "location" => "required",
            "latitude" => "required|numeric",
            "longitude" => "required|numeric",
            "type_id" => "required",
            "import_operation_id" => "required|integer"
        ]);


        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:4096'
        ]);
        if ($request->image != null) {
            $image = $request->file('image');
            $image_path = $image->store('Vehicles', 'public');

            $validated_values["img_path"] = 'storage/' . $image_path;
        }
        $vehicle = Vehicle::create($validated_values);

        return response()->json(["msg" => "vehicle added", "vehicle_data" => $vehicle], 201);
    }





    public function create_new_supplier(Request $request)
    {
        $validated_values = $request->validate([
            "comunication_way" => "required",
            "identifier" => "required",
            "country" => "required"
        ]);
        $supplier = Supplier::where("identifier", $validated_values["identifier"])->get();
        if ($supplier->isNotEmpty()) {
            return response()->json(["msg" => "supplier already exist", "supplier_data" => $supplier], 400);
        }


        $supplier = Supplier::create($validated_values);


        return response()->json(["msg" => "supplier added", "supplier_data" => $supplier], 201);
    }




    public function create_new_import_operation(Request $request)
    {
        $validated_values = $request->validate([
            "supplier_id" => "required|integer",
            "location" => "required",
            "latitude" => "required",
            "longitude" => "required"
        ]);

        $Data = $this->valedate_and_build($request);
        $validated_products = $Data["products"];
        $validated_vehicles = $Data["vehicles"];
        $errors_products = $Data["errors_products"];
        $errors_vehicles = $Data["errors_vehicles"];

        if (!empty($errors_vehicles) || !empty($errors_products)) {
            $import_key = Str::uuid();

            Cache::put($import_key, $validated_values, now()->addMinutes(60));

            $product_key = Str::uuid();
            Cache::put($product_key, $validated_products, now()->addMinutes(60));

            $vehicle_key = Str::uuid();
            Cache::put($vehicle_key, $validated_vehicles, now()->addMinutes(60));
            //add the arrays to the cash if i will correct the error $validated_products

            return response()->json([
                "msg" => "the import isnot created there are errors",
                "import_operation_key" => $import_key,
                "products_correction_key" => $product_key,
                "product_errors" => $errors_products,
                "vehicles_correction_key" => $vehicle_key,
                "errors_vehicles" => $errors_vehicles
            ]);
        }

        $import_operation = Import_operation::create($validated_values);


        importing_operation::dispatch($import_operation, $validated_products, $validated_vehicles);

        return response()->json(["msg" => "created", "errors" => $errors_products, "products" => $validated_products], 201);
    }

    public function suppourt_new_product(Request $request)
    {

        $validated_values = $request->validate([
            "name" => "required",
            "description" => "required",
            "import_cycle" => "string",
            "type_id" => "required"

        ]);
        $product = Product::where("name", $validated_values["name"])->get();


        if ($product->isNotEmpty()) {
            return response()->json(["msg" => "product already exist", "product_data" => $product], 400);
        }
        $product = Product::create($validated_values);

        return response()->json(["msg" => "product created", "product_data" => $product], 201);
    }


    public function correct_errors(Request $request)
    {
        $request->validate([
            "import_operation_key" => "required",

        ]);



        $validated_products = Cache::get($request->products_correction_key);

        $validated_vehicles = Cache::get($request->vehicles_correction_key);
        $import_operation = Cache::get($request->import_operation_key);

        $errors_products = null;
        $errors_vehicles = null;

        $Data = $this->valedate_and_build($request);

        $errors_products = $Data["errors_products"];
        $errors_vehicles = $Data["errors_vehicles"];
        $corrected_products = $Data["products"];
        $corrected_vehicles = $Data["vehicles"];


        $corrected_products = is_array($corrected_products) ? array_values($corrected_products) : [];
        $corrected_vehicles = is_array($corrected_vehicles) ? array_values($corrected_vehicles) : [];

        $validated_products = is_array($validated_products) ? array_values($validated_products) : [];
        $validated_vehicles = is_array($validated_vehicles) ? array_values($validated_vehicles) : [];


        $validated_products = array_merge($corrected_products, $validated_products);
        $validated_vehicles = array_merge($corrected_vehicles, $validated_vehicles,);




        if (!empty($errors_vehicles) || !empty($errors_products)) {
            Cache::forget($request->products_correction_key);
            Cache::forget($request->vehicles_correction_key);
            Cache::forget($request->import_job_key);


            $import_key = Str::uuid();
            Cache::put($import_key, $import_operation, now()->addMinutes(60));
            $product_key = Str::uuid();
            Cache::put($product_key, $validated_products, now()->addMinutes(60));
            $vehicle_key = Str::uuid();
            Cache::put($vehicle_key, $validated_vehicles, now()->addMinutes(60));
            //add the arrays to the cash if i will correct the error $validated_products

            return response()->json([
                "msg" => "the import isnot created there are errors",
                "import_operation_key" => $import_key,
                "products_correction_key" => $product_key,
                "product_errors" => $errors_products,
                "vehicles_correction_key" => $vehicle_key,
                "errors_vehicles" => $errors_vehicles
            ]);
        }

        $import_operation = Import_operation::create($import_operation);


        importing_operation::dispatch($import_operation, $validated_products, $validated_vehicles);
        Cache::forget($request->products_correction_key);
        Cache::forget($request->vehicles_correction_key);
        Cache::forget($request->import_job_key);

        return response()->json(["msg" => "created", "errors" => $errors_products], 201);
    }

    /* why import job and correct errors??
   import job require the supplier id and products info and vehicles info that mean you can add
   new products to your warehouses from the products you support them then you have pare example
   you support the red meat and more than supplier sell it for you with different expiration time and some detils
   then the imported products refers to the public product which is red meat in warehouses or ditribution centers
   you have red meat but from different import job and with different details but the public product is constant
    then you will sell the oldest first and know who is the
   supplier who send it? and can to resive the problems from costumer and know the source and you earn real
   system well why correct errors??? because the data entry may forget some details or enter some problems
   then i will save the correct in cache memore for a one houer and sned the errors
    to correct it with the details then i will resive it and fetvh the correct values and continue the pocess
    save the correct in cache and send the errors if ocure and wait the correction
   */
    //place is distributuion_center or warehouse
    // public function support_new_product_in_place(Request $request){
    //    $validated_values=$request->validate([
    //       "place"=>"required|in:Warehouse,Distribution_center",
    //       "place_id"=>"required|integer"
    //       ]);


    //    $data=$request->validate([

    //    "product_id"=>"required|integer",
    //    "max_load"=>"required|numeric",

    //  ]);

    //   $table="App\Models\\".$validated_values["place"]."_Product";

    //   $correct_id=strtolower($validated_values["place"]."_id");

    //    $data[$correct_id]=$validated_values["place_id"];
    //    $prod=$table::where($correct_id,$validated_values["place_id"])->where("product_id",$data["product_id"])->exists();
    //    if($prod){
    //       return response()->json(["msg"=>"already supported"],400);

    //    }
    //    $table::create($data);

    //    return response()->json(["msg"=>"supported"],201);

    //    }

    public function show_products()
    {
        $products = Product::all();
        foreach ($products as $product) {
            $totals = $product->sections()
                ->selectRaw('SUM(average) as total_avg, SUM(variance) as total_variance')
                ->first();
            $product["total_exist_quantity"] = $product->import_operation_details->where("status", "accepted")->count();
            $product["total_sold_quantity"] = $product->import_operation_details->where("status", "sold")->count();
            $product["total_rejected_quantity"] = $product->import_operation_details->where("status", "rejected")->count();

            $product["avirage"] = $totals->total_avg;
            $product["deviation"] = sqrt($totals->total_variance);
        }
        return response()->json(["msg" => "sucessfull", "products" => $products], 200);
    }


    //this method to try the algorithm of location
    public function orded_locations(Request $request)
    {
        //calculate($lat1, $lon1, $lat2, $lon2, $unit = 'km')
        $sorted_places = $this->sort_the_near_by_location("App\Models\DistributionCenter", $request->latitude, $request->longitude);
        return response()->json($sorted_places);
    }
    public function creeate_bill()
    {

        $sourc = DistributionCenter::find(1);
        $user = User::find(1);
        $user["location"] = "damas";

        $user["longitude"] = 33;
        $user["latitude"] = 35;

        $transfer = $this->transfers($sourc, $user, ["1" => 2], now());
        return response()->json(["trans" => $transfer], 201);
    }


    public function support_new_container(Request $request)
    {
        $validated_values = request()->validate([
            "name" => "required",
            "capacity" => "required|numeric",
            "product_id" => "required|integer"
        ]);

        $container = Containers_type::create($validated_values);
        return response()->json(["msg" => "created", "continer_data" => $container], 201);
    }



    public function create_new_section(Request $request)
    {
        $validated_values = $request->validate([
            "existable_type" => "required|in:DistributionCenter,Warehouse",
            "existable_id" => "required",
            "product_id" => "required",
            "num_floors" => "required|integer",
            "num_classes" => "required|integer",
            "num_positions_on_class" => "required|integer",
            "name" => "required"
        ]);

        $model = "App\\Models\\" . $validated_values["existable_type"];

        $place = $model::find($validated_values["existable_id"]);

        if (!$place) {
            return response()->json(["msg" => "the place which you want isnot exist"], 404);
        }
        try {
            $num_of_sections_on_place = $place->sections->count();

            if ($num_of_sections_on_place == $place->num_sections) {
                return response()->json(["msg" => "the place is full"], 400);
            }
        } catch (\Exception $e) {
        }
        $product = Product::find($validated_values["product_id"]);

        if (!$product) {
            return response()->json(["msg" => "the product which you want isnot exist"], 404);
        }

        if ($product->type->id != $place->type->id) {
            return response()->json(["msg" => "the type is not smothe"], 400);
        }

        $validated_values["existable_type"] = $model;

        $section = Section::create($validated_values);

        $this->create_postions("App\\Models\\Posetions_on_section", $section, "section_id");

        return response()->json(["msg" => "section created succesfully", "section" => $section], 201);
    }
    public function suppurt_new_storage_media(Request $request)
    {
        $validated_values = $request->validate([
            "name" => "required",
            "container_id" => "required|integer",
            "num_floors" => "required|integer",
            "num_classes" => "required|integer",
            "num_positions_on_class" => "required|integer",
        ]);

        $storage_element = Storage_media::create($validated_values);

        return response()->json(["msg" => "storage_element created succesfully", "sorage_element" => $storage_element], 201);
    }

    public function create_new_imporet_op_storage_media(Request $request)
    {
        $validated_values = $request->validate([
            "supplier_id" => "required|integer",
            "location" => "required",
            "latitude" => "required",
            "longitude" => "required"
        ]);

        $validated_items = $request->validate([
            'storage_media' => 'required|array|min:1',
            'storage_media.*.storage_media_id' => 'required|integer',
            'storage_media.*.quantity' => 'required|integer|min:1'

        ]);

        $storage_media = $validated_items["storage_media"];

        $import_operation = Import_operation::create($validated_values);



        import_storage_media::dispatch($import_operation->id, $storage_media);

        return response()->json(["msg" => "created succesfuly"], 201);
    }

    public function create_new_import_operation_product(Request $request)
    {
        // verify the inputs
        $validated_values = $request->validate([
            'import_operation_id' => 'required|integer|exists:import_operations,id',
        ]);

        $validated_products = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.expiration' => 'required|date',
            'products.*.producted_in' => 'required|date',
            'products.*.price_unit' => 'required|numeric|min:0'
        ]);

        $products = $validated_products["products"];

        // getting import operation
        $import_operation = Import_operation::findOrFail($validated_values['import_operation_id']);

        // use the job for proceeding adding the products
        importing_op_prod::dispatch($import_operation, $products);

        return response()->json(["msg" => "Products are being processed and added successfully."], 202);
    }
}
