<?php

namespace App\Http\Controllers;

use App\Http\Requests\storeProductRequest;
use App\Http\Resources\ProductResource;

use App\Http\Resources\TypeResource;
use App\Models\Bill;

use App\Models\type;
use App\Models\User;
use App\Models\Cargo;

use App\Models\Garage;
use App\Models\Employe;
use App\Models\Product;

use App\Models\Vehicle;
use App\Models\Favorite;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\Warehouse;
use App\Traits\CRUDTrait;
use App\Models\Bill_Detail;
use Illuminate\Http\Request;
use App\Models\Specialization;
use App\Models\Supplier_Product;
use App\Models\Transfer_Vehicle;
use App\Models\Werehouse_Product;
use App\Models\DistributionCenter;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\storeEmployeeRequest;
use App\Models\distribution_center_Product;

class SuperAdmenController extends Controller
{
   use CRUDTrait;

 

   public function create_new_product(storeProductRequest $request)
   {
      $validated_values = $request->validated();

      if ($request->hasFile('img_path')) {
         $path = $request->file('img_path')->store('products', 'public'); // تخزين الصورة في مجلد المنتجات
         $validated_data['img_path'] = $path;
      }
      $product = Product::create($validated_values);
      return response()->json(['message' => 'Product added successfully', 'product_data' => new ProductResource($product)], 201);
   }


  
   }










}
