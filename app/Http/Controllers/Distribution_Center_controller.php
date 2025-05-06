<?php

namespace App\Http\Controllers;

use App\Models\Employe;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\LoadingTrait;
use Symfony\Component\HttpKernel\HttpCache\ResponseCacheStrategy;

class Distribution_Center_controller extends Controller
{

    use LoadingTrait;
    public function show_my_suppurted_products(Request $request){
        
        $token = $request->bearerToken();
          
          
        $payload = JWTAuth::getPayload($token); 
        
        
        $manager= JWTAuth::parseToken()->authenticate('employe');
        $manager=Employe::find($payload->get("id"));
       
       
        //place is a warehouse or ditribution center
         $place=$manager->workable;
         print_r($place);
        $public_details=$place->public_details_about_products;
        $my_products=[];
         foreach($public_details as $details){
            $product=$details->product;
            $product->actual_load=$this->calculate_actual_load($details->product_details);
            $product->average=$details->average;
            $product->squrt($details->variance);
            $my_products[]=$product;
         }
         return response()->json(["msg"=>"i am happy","ypur_products"=>$product],202);

    }

    public function ask_products(Request $request){
      



    }
}
