<?php

namespace App\Http\Controllers;

use App\Http\Requests\storeUserRequest;
use App\Models\User;
use Hash;
use Illuminate\Http\Request;
use App\Traits\AlgorithmsTrait;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
  public function register_user(storeUserRequest $request){
  
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

}