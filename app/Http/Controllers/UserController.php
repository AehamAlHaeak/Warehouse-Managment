<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Traits\TokenUser;
use Illuminate\Http\Request;
use App\Traits\AlgorithmsTrait;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\storeUserRequest;
use App\Http\Requests\updateUserRequest;



use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validator;

class UserController extends Controller
{
    use TokenUser,AlgorithmsTrait;
    public function register_user(Request $request)
    {
      
         

        $validatedData = $request->validate([
            'name' => 'string',
            'last_name' => 'string',
            'location' => 'string',
            'birthday' => 'date',
            'email' => 'email',
            'phone_number' => 'string',
            'password' => 'required|string|min:6',
          ]);
        
    
        
        if (!empty($validatedData["email"])) {
                $user = User::where("email", $validatedData["email"])->first();
            } 
            if (!empty($validatedData["phone_number"])) {
                $user = User::where("phone_number",$validatedData["phone_number"])->first();
            } 
            if($user){
                return response()->json(["msg"=>"user already exist!"],400);
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
                "token"=>$token
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
        
        $validated_values = $request->validate([
            "email" => "email",
            "password" => "string",
            "phone_number" => "string",
        ]);

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

            if (empty($validated_values["password"])|| !Hash::check($validated_values["password"], $user->password) ) {
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
    public function updateUser(updateUserRequest $request)
    {
        $user = Auth()->user();
        $user_data = User::find($user->id);
        $data = $request->validated();
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



        $user_data->update(  $data);
        return response()->json(["msg"=> "updated seccessfully",'user'=>$user_data], 200);
    }catch (\Exception $e) {
        return response()->json([
            'error' => 'Something went wrong',
            'msg' => $e->getMessage(),
        ], 500);
    }
     }



    public function near_by_centers(Request $request){
        $location=$request->validate([
            'location'=>'required|string',
            'latitude'=>'required',
            'longitude'=>'required'
        ]);


$nearest_center=$this->calculate_the_nearest_location("App\Models\DistributionCenter",$request->latitude,$request->longitude);

return response()->json(["msg"=>"here the nearest","nearest"=>$nearest_center], 200);
    }
}
