<?php

namespace App\Http\Controllers;

use Exception;
use App\Jobs\sell;
use App\Models\Job;
use App\Models\type;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Vehicle;
use App\Models\Transfer;
use App\Traits\TokenUser;
use Illuminate\Validator;
use Illuminate\Http\Request;
use App\Models\Transfer_detail;

use App\Traits\AlgorithmsTrait;
use App\Models\reserved_details;
use App\Models\Continer_transfer;
use App\Models\DistributionCenter;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use App\Http\Requests\storeUserRequest;
use App\Http\Requests\updateUserRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use TokenUser, AlgorithmsTrait;
    public function register_user(Request $request)
    {


        try {
            $validatedData = $request->validate([
                'name' => 'string',
                'last_name' => 'string',
                'location' => 'string',
                'birthday' => 'date',
                'email' => 'email',
                'phone_number' => 'string',
                'password' => 'required|string|min:6',
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }


        if (!empty($validatedData["email"])) {
            $user = User::where("email", $validatedData["email"])->first();
        }
        if (!empty($validatedData["phone_number"])) {
            $user = User::where("phone_number", $validatedData["phone_number"])->first();
        }
        if ($user) {
            return response()->json(["msg" => "user already exist!"], 400);
        }
        try {
            $validatedData['password'] = Hash::make($validatedData['password']);

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('user_profile', 'public');
                $validatedData['img_path'] = "storage/" . $imagePath;
            }

            $user = User::create($validatedData);
            $token = $this->token_user($user);
            return response()->json([
                'msg' => 'Register user successfully',
                'user' => $user,
                "token" => $token
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'msg' => $e->getMessage(),
            ], 500);
        }
    }



    public function login_user(Request $request)
    {
        try {
            $validated_values = $request->validate([
                "email" => "email",
                "password" => "string",
                "phone_number" => "string",
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        try {

            if (!empty($validated_values["email"])) {
                $user = User::where("email", $validated_values["email"])->first();
            } elseif (!empty($validated_values["phone_number"])) {
                $user = User::where("phone_number", $validated_values["phone_number"])->first();
            } else {
                return response()->json(['msg' => 'Email or phone number is required'], 400);
            }

            if (!$user) {
                return response()->json(['msg' => 'User not found'], 404);
            }

            if (empty($validated_values["password"]) || !Hash::check($validated_values["password"], $user->password)) {
                return response()->json(['msg' => 'Invalid password'], 400);
            }

            $token = $this->token_user($user);


            return response()->json(['msg' => 'Logged in successfully', 'token' => $token], 200);
        } catch (Exception $e) {

            return response()->json(['msg' => $e->getMessage()], 500);
        }
    }



    public function logout_user(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            if ($token) {
                JWTAuth::invalidate();
                return response()->json(["msg" => "Successfully Logged out  "], 202);
            }
            return response()->json(["msg" => "No Token Found"], 400);
        } catch (\Exception $e) {
            return response()->json(["msg" => "Failed to logout, please try again later"], 500);
        }
    }
    public function updateUser(Request $request)
    {
        try {


            try {
                $data = $request->validate([
                    'name' => ' string',
                    'last_name' => 'string',
                    'location' => ' string',
                    'birthday' => ' date',
                    'email' => ' email|unique:users,email',
                    'phone_number' => ' string|unique:users,phone_number',
                    'password' => ' string|min:6',
                    'image' => ' image|mimes:jpeg,png,jpg,gif,bmp|max:4096',

                ]);
            } catch (ValidationException $e) {

                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            try {
                if (!empty($data['password'])) {
                    $data['password'] = Hash::make($data['password']);
                } else {
                    unset($data['password']);
                }
                if ($request->hasFile('image')) {
                    $imagePath = $request->file('image')->store('user_profile', 'public');
                    $data['img_path'] = 'storage/' . $imagePath;
                }
                unset($data['image']);

                $user = Auth()->user();

                // $user->update($data);
                return response()->json(["msg" => "updated seccessfully", 'user' => $user], 202);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Something went wrong',
                    'msg' => $e->getMessage(),
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'msg' => $e->getMessage(),
            ]);
        }
    }


    public function reserve_products(Request $request)
    {
        DB::beginTransaction();
        try {
            try {
                $valedated_values = $request->validate([
                    "dist_c_id" => "required",
                    "order" => "array|min:1",

                    "location" => "required",
                    "latitude" => "required|numeric",
                    "longitude" => "required|numeric",
                    "type" => "required|in:transfered,Un_transfered",
                    "status" => "required|in:accepted,wait",
                ]);
            } catch (ValidationException $e) {

                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            $user = Auth()->user();
            $invoice = Invoice::create([
                "user_id" => $user->id,
                "type" => $valedated_values["type"],
                "status" => $valedated_values["status"],
            ]);
            $dist_C = DistributionCenter::find($valedated_values["dist_c_id"]);
            if (!$dist_C) {
                return response()->json(["msg" => "the distributin center not found"], 404);
            }
            $transfer = Transfer::create([
                "sourceable_id" => $dist_C->id,
                "sourceable_type" => "App\Models\DistributionCenter",
                "destinationable_id" => $user->id,
                "destinationable_type" => "App\Models\User",
                "invoice_id" => $invoice->id,
                "location" => $valedated_values["location"],
                "latitude" => $valedated_values["latitude"],
                "longitude" => $valedated_values["longitude"]
            ]);

            foreach ($valedated_values["order"] as $id => $quantity) {
                $product = Product::find($id);
                if (!$product) {
                    DB::rollBack();
                    return response()->json(["msg" => "the product not found"], 404);
                }
                $continer = $product->container;
                $number_contiers = ceil($quantity / $continer->capacity);
                $product = $this->inventry_product_in_place($product, $dist_C);
                $min_Capacity = 2;
                if ($product->actual_load < $quantity) {

                    unset(
                        $product->max_load,
                        $product->average,
                        $product->deviation,
                        $product->salled_load,
                        $product->rejected_load,
                        $product->reserved_load,
                        $product->auto_rejected_load
                    );

                    DB::rollBack();
                    return response()->json(["msg" => "the quantity is not enogh ", "product" => $product], 404);
                }

                if ($valedated_values["type"] == "transfered") {


                    $garages = $dist_C->garages()->pluck("id")->toArray(); //,,,

                    $continers = [];
                    if (!empty($garages)) {

                        $min_Capacity = Vehicle::whereIn("garage_id", $garages)->min("capacity");
                    }
                    if (is_null($min_Capacity)) {
                        return response()->json(["msg" => "the order is not enogh to transfer it by us ", "DistributionCenter" => $dist_C], 400);
                    }
                    $dist_C = $this->calculate_ready_vehiscles($dist_C, $product);
                    if ($dist_C->can_to_translate_load < $quantity) {

                        DB::rollBack();

                        return response()->json(["msg" => "the vehicles is not enogh in the dist center ", "DistributionCenter" => $dist_C], 404);
                    }
                }
                $transfer_detail = Transfer_detail::create([
                    "transfer_id" => $transfer->id,
                    "status" => "wait"
                ]);






                if ($number_contiers > $min_Capacity / 2) {

                    $continers = $this->reserve_product_in_place($dist_C, $transfer_detail, $product, $quantity, $choise = "complete");
                } else {

                    $continers = $this->reserve_product_in_place($dist_C, $transfer_detail, $product, $quantity, $choise = "un_complete");
                }



                if ($continers == "no quantity" || $continers == "no enogh quantity in  this place to reserve") {
                    throw new \Exception($continers);
                } else {



                    foreach ($continers as $continer_id) {
                        Continer_transfer::create([
                            "transfer_detail_id" =>  $transfer_detail->id,
                            "imp_op_contin_id" => $continer_id
                        ]);
                    }
                }
            }

            $job = new sell($invoice->id);
            if ($valedated_values["status"] == "accepted") {
                $jobId = Queue::later(now()->addMinutes(0), $job);
            } else {
                $jobId = Queue::later(now()->addMinutes(60), $job);
            }
            $invoice->job_id = $jobId;
            $invoice->save();

            DB::commit();
            return response()->json(["msg" => "success", "continers" => $continers, "invoice" => $invoice], 202);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(["error" => $e->getMessage()], 400);
        }
    }

    public function delete_invoice(Request $request, $invoice_id)
    {
        DB::beginTransaction();
        try {
            $invoice = Invoice::find($invoice_id);
            if (!$invoice) {
                return response()->json(["msg" => "the invoice not found"], 404);
            }
            $user = Auth::user();
            if ($invoice->user_id != $user->id) {
                return response()->json(["msg" => "the invoice not found"], 404);
            }
            if ($invoice->status == "accepted") {
                return response()->json(["msg" => "cannot delete the invoice it is implemented!"], 404);
            }
            $transfer = $invoice->transfers;
            foreach ($transfer as $transfer) {
                $transfer_details = $transfer->transfer_details;
                foreach ($transfer_details as $transfer_detail) {
                    Continer_transfer::where("transfer_detail_id", $transfer_detail->id)->delete();
                    reserved_details::where("transfer_details_id", $transfer_detail->id)->delete();
                    $transfer_detail->delete($transfer_detail->id);
                }
                $transfer->delete($transfer->id);
            }

            $invoice->delete($invoice->id);
            DB::commit();
            return response()->json(["msg" => "success"], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(["error" => $e->getMessage()], 400);
        }
    }
    public function show_products_in_centers()
    {
        try {
            $types = type::all();
            foreach ($types as $type) {
                $products = $type->products;

                $dis_Cs = $type->distribution_centers;

                unset($type->distribution_centers);
                foreach ($products as $product) {
                    $actual_load = 0;

                    foreach ($dis_Cs as $dis_C) {
                        $product = $this->inventry_product_in_place($product, $dis_C);
                        $actual_load += $product->actual_load;
                    }
                    unset(
                        $product->updated_at,
                        $product->created_at,
                        $product->actual_load,
                        $product->max_load,
                        $product->avilable_load,
                        $product->average,
                        $product->deviation,
                        $product->salled_load,
                        $product->rejected_load,
                        $product->reserved_load,
                        $product->auto_rejected_load
                    );

                    $product->actual_load = $actual_load;
                }
                $type->calcculated_products = $products;
            }
            return response()->json(["types" => $types], 202);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()], 400);
        }
    }


    public function show_distribution_centers_of_product_sorted($product_id, $longitude, $latitude)
    {
        try {
            $product = Product::find($product_id);
            if (!$product) {
                return response()->json(["msg" => "product not found"], 404);
            }
            $type = $product->type;
            unset($product->type);
            $distribution_centers = DistributionCenter::where("type_id", $type->id)->whereHas("sections", function ($q) use ($product) {
                $q->where("product_id", $product->id);
            })->get();

            $distribution_centers = $this->sort_the_near_by_location($distribution_centers, $latitude, $longitude);

            return $distribution_centers;
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()], 400);
        }
    }
    public function show_products_of_distribution_center($dist_c_id)
    {
        try {
            $dist_C = DistributionCenter::find($dist_c_id);
            if (!$dist_C) {
                return response()->json(["msg" => "distribution center not found"], 404);
            }
            $sections = $dist_C->sections->unique('product_id');
            $products = collect();
            foreach ($sections as $section) {
                $product = $section->product;
                $product = $this->inventry_product_in_place($product, $dist_C);
                $this->calculate_ready_vehiscles($dist_C, $product);
                $product->can_transfer = $dist_C->can_to_translate_load;
                $products->push($product);
                $continer = $product->container;
                unset(
                    $product->container,
                    $product->updated_at,
                    $product->created_at,
                    $product->actual_load,
                    $product->max_load,
                    $product->avilable_load,
                    $product->average,
                    $product->deviation,
                    $product->salled_load,
                    $product->rejected_load,
                    $product->reserved_load,
                    $product->auto_rejected_load
                );
                $garages = $dist_C->garages()->pluck("id")->toArray(); //,,,

                $min_Capacity = 0;
                if (!empty($garages)) {

                    $min_Capacity = (Vehicle::whereIn("garage_id", $garages)->min("capacity") / 2) * $continer->capacity;
                }
                $product->min_Capacity_to_transfer = $min_Capacity;
            }
            return response()->json(["products" => $products], 202);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()], 400);
        }
    }


    public function show_my_invoices()
    {
        try {
            $user = Auth::user();
            $invoices = $user->invoices;
            foreach ($invoices as $invoice) {
                $first_transfer = $invoice->transfers()->first();
                $invoice->source = $first_transfer->sourceable;
            }
            return response()->json(["invoices" => $invoices], 202);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()], 400);
        }
    }

    public function show_invoice_loads($invoice_id)
    {
        try {
            $invoice = Invoice::find($invoice_id);
            if (!$invoice) {
                return response()->json(["msg" => "the invoice not found"], 404);
            }
            $user = Auth::user();
            if ($invoice->user_id != $user->id) {
                return response()->json(["msg" => "the invoice not for you"], 404);
            }

            $transfers = $invoice->transfers;
            $details = collect();
            foreach ($transfers as $transfer) {
                $loads = $transfer->transfer_details;
                foreach ($loads as $load) {



                    $data = $this->reserved_sold_on_load($load);
                    $load->reserved_load = $data["reserved"];
                    $load->sell_load = $data["sold"];
                    if ($load->vehicle_id == null) {
                        $first_continer = $load->continers->first();
                        $parent_continer = $first_continer->parent_continer;
                        unset($load->continers);
                        $product = $parent_continer->product;
                        $load->product = $product;
                        $load->vehicle = "no vehicle from our company";
                    } else {
                        $vehicle = $load->vehicle()->select(["id", "name", "size_of_vehicle", "capacity", "product_id"])->first();
                        $product = $vehicle->product;
                        unset($vehicle->product);
                        $load->product = $product;
                        unset($vehicle->product_id);
                        $load->vehicle = $vehicle;
                        if ($load->status == "cut") {
                            $load->status = "replacement has been sent";
                        }
                    }
                }
                $details = $details->concat($loads);
            }
            return response()->json(["loads" => $details], 202);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()], 400);
        }
    }

    public function delete_load($load_id)
    {
        DB::beginTransaction();
        try {
            $transfer_detail = Transfer_detail::find($load_id);
            if (!$transfer_detail) {
                return response()->json(["msg" => "the load not found"], 404);
            }
            $transfer = $transfer_detail->transfer;

            $invoice = $transfer->invonice;

            $user = Auth::user();
            if ($invoice->user_id != $user->id) {
                return response()->json(["msg" => "the invoice not for you"], 401);
            }
            if ($invoice->status == "accepted") {
                return response()->json(["msg" => "the invoice is implemented"], 401);
            }
            Continer_transfer::where("transfer_detail_id", $transfer_detail->id)->delete();
            reserved_details::where("transfer_details_id", $transfer_detail->id)->delete();
            $transfer_detail->delete($transfer_detail->id);
            DB::commit();
            return response()->json(["msg" => "the load deleted"], 202);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(["error" => $e->getMessage()], 400);
        }
    }

    public function edit_load(Request $request)
    {
        DB::beginTransaction();
        try {
            try {
                $validated_values = $request->validate([
                    "load_id" => "required|integer",
                    "quantity" => "required|integer",
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            $transfer_detail = Transfer_detail::find($validated_values["load_id"]);
            if (!$transfer_detail) {
                return response()->json(["msg" => "the load not found"], 404);
            }
            $transfer = $transfer_detail->transfer;
            $invoice = $transfer->invonice;
            $user = Auth::user();
            if ($invoice->user_id != $user->id) {
                return response()->json(["msg" => "the invoice not for you"], 401);
            }
            if ($invoice->status == "accepted") {
                return response()->json(["msg" => "the invoice is implemented"], 401);
            }
            $old_reserve = $this->reserved_sold_on_load($transfer_detail)["reserved"];
            if ($old_reserve == $validated_values["quantity"]) {
                return response()->json(["msg" => "the quantity not changed"], 400);
            }
            $dist_C = $transfer->sourceable;
            if ($invoice->type = "transfered") {
            
                $continer = $transfer_detail->continers()->first();
                $parent_continer = $continer->parent_continer;
                $product = $parent_continer->product;
                $min_Capacity = $parent_continer->cpacity;
                
                $garages = $dist_C->garages()->pluck("id")->toArray();

                if (!empty($garages)) {

                    $min_Capacity = (Vehicle::whereIn("garage_id", $garages)->min("capacity") / 2) * $parent_continer->capacity;
                    
                }
                if (is_null($min_Capacity)) {
                    return response()->json(["msg" => "the order is not enogh to transfer it by us ", "DistributionCenter" => $dist_C], 400);
                }
                if($validated_values["quantity"]<$min_Capacity){
                   return response()->json(["msg" => "the order is not enogh to transfer it by us ", "DistributionCenter" => $dist_C], 400);
                }
                $dist_C = $this->calculate_ready_vehiscles($dist_C, $product);
                if ($dist_C->can_to_translate_load < $validated_values["quantity"]) {

                    DB::rollBack();

                    return response()->json(["msg" => "the vehicles is not enogh in the dist center ", "DistributionCenter" => $dist_C], 404);
                }
            }
            if ($old_reserve < $validated_values["quantity"]) {
                $new_quantity = $validated_values["quantity"] - $old_reserve;
                if ($validated_values["quantity"] > $min_Capacity) {
                    $out_put = $this->reserve_product_in_place($dist_C, $transfer_detail, $product, $new_quantity, "complete");
                } else {
                    $out_put = $this->reserve_product_in_place($dist_C, $transfer_detail, $product, $new_quantity, "un_partial");
                }


                if ($out_put == "no enogh quantity in  this place to reserve" || $out_put == "no quantity") {
                    DB::rollBack();
                    return response()->json(["msg" => $out_put], 400);
                }
            }
            elseif($old_reserve > $validated_values["quantity"]) {

                $surplus_quantity = $old_reserve - $validated_values["quantity"];
                $reserved_loads = $transfer_detail->reserved_loads()->orderByDesc('id')->get();
                 while($surplus_quantity >0){
                    $reserved_load=$reserved_loads->splice(0, 1)->first();
                    
                  $new_reserve=min($reserved_load->reserved_load,$surplus_quantity);
               
                  $surplus_quantity-=$new_reserve;
                  $reserved_load->reserved_load-=$new_reserve;
                  
                  if($reserved_load->reserved_load==0){
                      $reserved_load->delete($reserved_load->id);
                      $parent_load=$reserved_load->parent_load;
                      $parent_continer=$parent_load->container;
                    
                      Continer_transfer::where("transfer_detail_id",$transfer_detail->id)->where("imp_op_contin_id",$parent_continer->id)->delete();
                      
                  }
                  else{
                    $reserved_load->save();
                  }
                 }
            }
 

           DB::commit();
                      $new_reserve=$this->reserved_sold_on_load($transfer_detail)["reserved"];
           return response()->json(["msg" => "the load edited","new_quantity"=> $new_reserve], 202);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(["error" => $e->getMessage()], 400);
        }
    }
    public function execute_invoice($invoice_id){
       DB::beginTransaction();
       try{
           $invoice=Invoice::find($invoice_id);
           if(!$invoice){
             return response()->json(["msg" => "the invoice not found"], 404);
           }
           $user=Auth::user();
           if($invoice->user_id!=$user->id){
            return response()->json(["msg" => "the invoice not for you"], 401);
           }
           if($invoice->status=="accepted"){
            return response()->json(["msg" => "the invoice is implemented"], 400);
           }
          $job=Job::find($invoice->job_id);
          if($job){
          $job->delete($job->id); 
          }
        
            $job = new sell($invoice->id);
            $jobId = Queue::later(now()->addMinutes(0), $job);
            $invoice->job_id = $jobId;
            $invoice->save();
            DB::commit();
        return response()->json(["msg" => "the invoice is under execute","invoice_id"=>$invoice_id,"job_id"=>$jobId], 202);
        
       }
       catch (Exception $e) {
            DB::rollBack();
            return response()->json(["error" => $e->getMessage()], 400);
        }
    }



}
