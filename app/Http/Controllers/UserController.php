<?php

namespace App\Http\Controllers;

use Exception;
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
use App\Models\DistributionCenter;
use App\Models\Continer_transfer;

use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
                return response()->json(["msg" => "Successfully Logged out  "], 200);
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
                    return response()->json(["msg" => "the quantity is npt enogh ", "product" => $product], 404);
                }

                if ($valedated_values["type"] == "transfered") {


                    $garages = $dist_C->garages()->pluck("id")->toArray(); //,,,

                    $continers = [];
                    if (!empty($garages)) {

                        $min_Capacity = Vehicle::whereIn("garage_id", $garages)->min("capacity");
                    }
                    if (is_null($min_Capacity) || $number_contiers < $min_Capacity / 2) {
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



            DB::rollBack();
            return response()->json(["msg" => "success", "continers" => $continers], 202);
            //DB::afterCommit(function () {
            //  dispatch(new YourJob(...));
            //});
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(["error" => $e->getMessage()], 400);
        }
    }
}
