<?php

namespace App\Http\Controllers;

use App\Http\Requests\storeUserRequest;
use App\Models\User;
use Exception;
use Hash;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\Token_user;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use Token_user;
    public function register_user(storeUserRequest $request)
    {

        $validatedData = $request->validated();
        try {
            $validatedData['password'] = Hash::make($validatedData['password']);

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('user_profile', 'public');
                $validatedData['img_path'] = $imagePath;
            }
            unset($validatedData['image']);
            $user = User::create($validatedData);

            return response()->json([
                'msg' => 'Register user successfully',
                'user' => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }

    }



    public function login_user(Request $request)
    {

        $validated_values = $request->validate([
            "email" => "nullable|email",
            "password" => "required",
            "phone_number" => "nullable|string",
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

            if (!Hash::check($validated_values["password"], $user->password)) {
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
}