<?php

namespace App\Http\Controllers;

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
use App\Traits\AlgorithmsTrait;
use Illuminate\Validation\Rule;
use App\Models\Import_operation;
use App\Models\Supplier_Details;
use App\Models\Supplier_Product;
use App\Models\Transfer_Vehicle;
use App\Jobs\importing_operation;
use App\Models\Werehouse_Product;

use App\Jobs\import_storage_media;
use App\Models\DistributionCenter;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Import_op_storage_md;
use App\Models\Posetions_on_section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\ProductResource;
use function PHPUnit\Framework\isEmpty;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\storeProductRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\storeEmployeeRequest;

use App\Models\Distribution_center_Product;
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



    public function create_new_garage(Request $request)
    {
        $validated_values = $request->validate([
            "existable_type" => "string|in:Warehouse,DistributionCenter",
            "existable_id" => "integer",
            "type" => "required|in:big,medium",
            "location" => "string",
            "latitude" => "numeric",
            "longitude" => "numeric",
            "max_capacity" => "required|integer"

 ]);

 $garage=Garage::create($validated_values);

        return response()->json(["msg" => "created", "garage" => $garage], 201);
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






    public function suppourt_new_product(Request $request)
    {

        $validated_values = $request->validate([
            "name" => "required",
            "description" => "required",
            "import_cycle" => "string",
            "type_id" => "required",
            "actual_piece_price"=>"required|numeric",
            "unit"=>"required",
            "quantity"=>"required"
        ]);
        $product = Product::where("name", $validated_values["name"])->get();


        if ($product->isNotEmpty()) {
            return response()->json(["msg" => "product already exist", "product_data" => $product], 400);
        }
        $product = Product::create($validated_values);

        return response()->json(["msg" => "product created", "product_data" => $product], 201);
    }




    public function show_products()
    {
        $products = Product::all();

            foreach ($products as $product) {
             $actual_load_in_warehouses=0;
             $actual_load_in_distribution_centers=0;
             $max_load_in_warehouses=0;
             $max_load_in_distribution_centers=0;
             $avilable_load_in_warehouses=0;
             $max_load_in_distribution_centers=0;
             $average_in_warehouses=0;
             $deviation_in_warehouses=0;
             $sections=$product->sections;
             foreach($sections as $section ){
              $areas_on_section=$this->calculate_areas($section);
               if($section->existable_type=="App\\Models\\Warehouse"){
                 $actual_load_in_warehouses+=$areas_on_section["max_capacity"]-$areas_on_section["avilable_area"];
                 $max_load_in_warehouses+=$areas_on_section["max_capacity"];
                 $avilable_load_in_warehouses+=$areas_on_section["avilable_area"];
                 $date = Carbon::parse($section->created_at);

                 $now = Carbon::now();
                 $weeksPassed = $date->diffInWeeks($now);
                 if( $weeksPassed!=0){

                    $deviation_in_warehouses+=sqrt($product->import_cycle/7)*sqrt($section->variance/$weeksPassed);
                 }

                    $average_in_warehouses+=($product->import_cycle/7)*$section->average;

               }
               if($section->existable_type=="App\\Models\\DistributionCenter"){
                 $actual_load_in_distribution_centers+=$areas_on_section["max_capacity"]-$areas_on_section["avilable_area"];
                 $max_load_in_distribution_centers+=$areas_on_section["max_capacity"];
                 $max_load_in_distribution_centers+=$areas_on_section["avilable_area"];
               }

             }
             $product->avilable_load_on_warehouses=$avilable_load_in_warehouses;
             $product->avilable_load_on_distribution_centers=$max_load_in_distribution_centers;
             $product->max_load_on_warehouse=$max_load_in_warehouses;
             $product->max_load_in_distribution_centers=$max_load_in_distribution_centers;
             $product->actual_load_in_warehouses=$actual_load_in_warehouses;
             $product->actual_load_in_distribution_centers=$actual_load_in_distribution_centers;
             $product->average= $average_in_warehouses;
             $product->deviation=$deviation_in_warehouses;
             $product->max_load_on_company=$max_load_in_warehouses+$max_load_in_distribution_centers;
             $product->load_on_company==$actual_load_in_warehouses+$actual_load_in_distribution_centers;
              unset($product["sections"]);
        }
        return response()->json(["msg" => "sucessfull", "products" => $products], 200);
    }


public function show_places_of_products($product_id){
  $warehouses=[];
  $distribution_centers=[];
  $product=Product::find($product_id);
   $sections=$product->sections;
   foreach($sections as $section){
    $place=$section->existable;
    if($section->existable_type=="App\\Models\\DistributionCenter"){
      $distribution_centers[$place->id]=$place;
     }

     if($section->existable_type=="App\\Models\\Warehouse"){
      $warehouses[$place->id]=$place;
     }

   }
 return response()->json(["msg"=>"here the places!","warehouses"=>$warehouses,"distribution_centers"=>$distribution_centers]);

}

public function delete_product($product_id){
 $product=Product::find($product_id);

if (!$product) {
        return response()->json(['error' => 'Product not found.'], 404);
    }

    $threshold = Carbon::now()->subMinutes(30);

    if ($product->created_at >= $threshold) {
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully.']);
    } else {
        return response()->json(['error' => 'Cannot delete: product is older than 30 minutes.'], 403);
    }


}


public function edit_product(Request $request)
{
    $product = Product::find($request->product_id);

    if (!$product) {
        return response()->json(['error' => 'Product not found.'], 404);
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
        // إذا لسا بأول 30 دقيقة، اسم و وصف و نوع كمان بيتحدثو
        $alwaysUpdatable = array_merge($alwaysUpdatable, ['name', 'type_id']);
    } else {
        if ($request->hasAny(['name', 'type_id'])) {
            return response()->json([
                'error' => 'You can\'t edit name, description, or type after 30 minutes.'
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
        'message' => 'Product updated successfully.',
        'product' => $product
    ]);
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
         $keys=$request->validate([
        "import_operation_key"=>"string",
        "storage_media_key"=>"string"
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
            'storage_media.*.section_id' => 'required|integer|min:1'
        ]);

        $storage_media = $validated_items["storage_media"];


        $storage_media_key=null;
        $import_operation_key=null;


         if(empty($keys["storage_media_key"]) || empty($keys["import_operation_key"])  )
         {

            $time=now();
                $storage_media_key="storage_media".$time;
                $import_operation_key="import_operation".  $time;
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


        Cache::put($storage_media_key,$storage_media,now()->addMinutes(60));
        Cache::put($import_operation_key,$validated_values,now()->addMinutes(60));

         }

        else{
         Cache::forget($keys["storage_media_key"]);
         Cache::forget($keys["import_operation_key"]);


        $storage_media_key=$keys["storage_media_key"];
        $import_operation_key=$keys["import_operation_key"];

        $import_op_storage_media_keys=Cache::get("import_op_storage_media_keys");
        $import_op_storage_media_keys[$keys["import_operation_key"]]["import_operation_key"]=$keys["import_operation_key"];
        $import_op_storage_media_keys[$keys["import_operation_key"]]["storage_media_key"]=$keys["storage_media_key"];
        Cache::forget("import_op_storage_media_keys");

        Cache::put("import_op_storage_media_keys",$import_op_storage_media_keys,now()->addMinutes(60));

        Cache::put($storage_media_key,$storage_media,now()->addMinutes(60));
        Cache::put($import_operation_key,$validated_values,now()->addMinutes(60));

        }


        return response()->json([
        "msg" => "saved for one hour conferm it or edit or after it will be lost",
        "storage_media_key"=>$storage_media_key,
        "import_operation_key"=>$import_operation_key,
         "supplier_id" => $validated_values["supplier_id"],
            "location" => $validated_values["location"],
            "latitude" =>  $validated_values["latitude"],
            "longitude" =>  $validated_values["longitude"],
        "storage_media"=>$storage_media], 201);
    }



    public function accept_import_op_storage_media(Request $request){

    $storage_media=Cache::get($request->storage_media_key);
    $import_operation=Cache::get($request->import_operation_key);
    if( !$storage_media || !$import_operation){
     return response()->json(["msg"=>"already accepted or deleted"],400);
    }
    $import_operation=Import_operation::create($import_operation);
    Cache::forget( $request->import_operation_key);
    Cache::forget( $request->storage_media_key);

    import_storage_media::dispatch($import_operation->id, $storage_media);

return response()->json(["msg"=>"storage_media under creating"],202);

}




public function show_latest_import_op_storage_media(){
    $import_op_storage_media_keys=Cache::get("import_op_storage_media_keys");
    $import_operations=[];
    $i=1;
    if(!$import_op_storage_media_keys){
         return response()->json(["no operation"]);
}

     foreach($import_op_storage_media_keys as $element){
       $import_operation=Cache::get($element["import_operation_key"]);
       $storage_media=Cache::get($element["storage_media_key"]);
       if(!$import_operation || !$storage_media ){
      continue;
        }
       $element["supplier_id"]=$import_operation["supplier_id"];
       $element["supplier"]=Supplier::find($import_operation["supplier_id"]);
        $element["location"]=$import_operation["location"];
         $element["latitude"]=$import_operation["latitude"];
          $element["longitude"]=$import_operation["longitude"];
           $j=1;
           foreach($storage_media as $storage_element){

            $section=Section::find($storage_element["section_id"]);
            $section_empty_posetions = $section->posetions()->whereNull('storage_media_id')->get();
            $storage_media[$j]["empty_capacity"]=$section_empty_posetions->count();

             $j++;
           }
           $element["storage_media"]=$storage_media;
             $import_operations[$i]= $element;
             $i++;
     }

     return response()->json(["import_operations"=>$import_operations]);
}





    public function create_new_import_operation_product(Request $request)
    {

            $keys=[
            "import_operation_key"=> $request-> import_operation_key,
            "products_key"=>$request->products_key
            ];

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
    'products.*.special_description' =>'string',
    'products.*.imported_load' => 'numeric|min:0',
    "products.*.distribution" => "required|array",
    "products.*.distribution.*.warehouse_id" => "required|integer",
    "products.*.distribution.*.load" => "required|min:1"

]);


        $products_key=null;
        $import_operation_key=null;


        $products = $validated_products["products"];



         if(empty($keys["products_key"]) || empty($keys["import_operation_key"])  )
         {

            $time=now();
                $products_key="products_".$time;
                $import_operation_key="import_operation".$time;
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


        Cache::put($products_key,$products,now()->addMinutes(60));
        Cache::put($import_operation_key,$validated_values,now()->addMinutes(60));

         }

        else{
         Cache::forget($keys["products_key"]);
         Cache::forget($keys["import_operation_key"]);


        $products_key=$keys["products_key"];
        $import_operation_key=$keys["import_operation_key"];

        $import_op_products_keys=Cache::get("import_op_storage_media_keys");
        $import_op_products_keys[$keys["import_operation_key"]]["import_operation_key"]=$keys["import_operation_key"];
        $import_op_products_keys[$keys["import_operation_key"]]["products_key"]=$keys["products_key"];
        Cache::forget("import_op_storage_media_keys");

        Cache::put("import_op_storage_media_keys",$import_op_products_keys,now()->addMinutes(60));

        Cache::put($products_key,$products,now()->addMinutes(60));
        Cache::put($import_operation_key,$validated_values,now()->addMinutes(60));

        }


        return response()->json(["msg" => "saved for one hour conferm it or edit or after it will be lost",
        "products_key"=>$products_key,
        "import_operation_key"=>$import_operation_key,
         "supplier_id" => $validated_values["supplier_id"],
            "location" => $validated_values["location"],
            "latitude" =>  $validated_values["latitude"],
            "longitude" =>  $validated_values["longitude"],
        "products"=>$products], 201);
    }

public function accept_import_op_products(Request $request){

    $products=Cache::get($request->products_key);
    $import_operation=Cache::get($request->import_operation_key);
    if( !$products || !$import_operation){
     return response()->json(["msg"=>"already accepted or deleted"],400);
    }
    $import_operation=Import_operation::create($import_operation);
    Cache::forget( $request->import_operation_key);
    Cache::forget( $request->storage_media_key);

    importing_op_prod::dispatch($import_operation, $products);

return response()->json(["msg"=>"storage_media under creating"],202);

}


public function reject_import_op(Request $request){
$elements=Cache::get($request->key);
$import_operation=Cache::get($request->import_operation_key);
    if( !$elements || !$import_operation){
     return response()->json(["msg"=>"already accepted or deleted"],400);
    }
    Cache::forget( $request->import_operation_key);
    Cache::forget( $request->key);

    return response()->json(["msg"=>"import opertation rejected successfuly"],200);
}

public function show_latest_import_op_products(){
     $import_op_products_keys=Cache::get("import_op_products_keys");
    $import_operations=[];

    $i=0;
    if(!$import_op_products_keys){
         return response()->json(["no operation"]);
}

     foreach($import_op_products_keys as $element){

       $import_operation=Cache::get($element["import_operation_key"]);
       $products=Cache::get($element["products_key"]);
       if(!$import_operation || !$products){

      continue;
        }
       $element["supplier_id"]=$import_operation["supplier_id"];
       $element["supplier"]=Supplier::find($import_operation["supplier_id"]);
        $element["location"]=$import_operation["location"];
         $element["latitude"]=$import_operation["latitude"];
          $element["longitude"]=$import_operation["longitude"];

             $element["products"]=$products;
             $i=0;
            foreach($element["products"] as $product){
                $element["products"][$i]["product"]=Product::find($product["product_id"]);
                $j=0;

                foreach($element["products"][$i]["distribution"] as  $dist){

                 $warehouse=Warehouse::find($dist["warehouse_id"]);


                 $dist["warehouse"]=$this->calcute_areas_on_place_for_a_specific_product($warehouse,$element["products"][$i]["product_id"]);
                $element["products"][$i]["distribution"][$j]["warehouse"]=$dist["warehouse"];
                 $j++;
                }
                $i++;
            }


             $import_operations[$i]= $element;

     }

     return response()->json(["import_operations"=>$import_operations]);






}

    public function create_import_op_vehicles(Request $request)
    {
    $keys = [
        "import_operation_key" => $request->import_operation_key,
        "vehicles_key" => $request->vehicles_key
    ];

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
        'vehicles.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,bmp|max:4096',
        'vehicles.*.capacity' => 'required|integer',
        'vehicles.*.type_id' => 'required|integer|exists:types,id',
        'vehicles.*.place_type' => 'required|in:Warehouse,DistributionCenter',
        'vehicles.*.place_id' => 'required|integer'
    ]);

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

       StoreVehiclesJob::dispatch($vehicles,$import_operation->id,$import_operation->location,$import_operation->longitude,$import_operation->latitude );

        return response()->json(["msg" => "vehicles under creating"], 202);

    }


   public function add_new_supplies_to_supplier(Request $request){
     $validated_values=$request->validate([
         "supplier_id"=>"required",
         "suppliesable_type"=>"required|in:Product,Storage_media",
         "suppliesable_id"=>"required",
         "max_delivery_time_by_days"=>"required|numeric"

     ]);

      $supplier=Supplier::find($validated_values["supplier_id"]);

      if(!$supplier){
        return response()->json(["msg"=>"supplier is not exist!"],404);
      }
      $model="App\\Models\\".$validated_values["suppliesable_type"];

      $supplies=$model::find($validated_values["suppliesable_id"]);

      if(!$supplies){

         return response()->json(["msg"=>"supplies is not exist!"],404);
      }
      $validated_values["suppliesable_type"]=$model;
       $supplies_info=Supplier_Details::where("supplier_id",$validated_values["supplier_id"])->
       where("suppliesable_type",$validated_values["suppliesable_type"])->
       where("suppliesable_id",$validated_values["suppliesable_id"])->get();

       if(!$supplies_info->isEmpty()){

           return response()->json(["msg"=>"supplier already suport that!??"],400);
       }



      Supplier_Details::create($validated_values);

      return response()->json(["msg"=>"now the supplier support that","supplies"=>$supplies],201);


   }

 public function show_suppliers(){
   $suppliers=Supplier::all();
   return response()->json(["suppliers"=>$suppliers]);

 }

public function show_products_of_supplier($id){
   $supplier=Supplier::find($id);
   if(!$supplier){
    return response()->json(["msg"=>"supplier is npt exist"],400);
   }

   $supplier_product=$supplier->supplier_products;


    return response()->json(["supplier_products"=>$supplier_product],200);
 }

 public function show_warehouses_of_product($id){
    $product=Product::find($id);

    $type=$product->type;


    $warehouses=$type->warehouses;


    $warehouses_with_details=[];
    $i=1;
    foreach($warehouses as $warehouse){

       $warehouse=$this->calcute_areas_on_place_for_a_specific_product($warehouse,$id);

        $warehouses_with_details[$i]=$warehouse;

        $i++;
       }
     return response()->json(["msg"=>"here the warehouses","warehouses"=>$warehouses_with_details]);
 }


    public function show_storage_media_of_supplier($id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json(["msg" => "supplier is npt exist"], 400);
        }

        $supplier_storage_media = $supplier->supplier_storage_media;


        return response()->json(["supplier_storage_media" => $supplier_storage_media], 200);
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
}
