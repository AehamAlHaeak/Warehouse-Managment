<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employe;
use App\Traits\LoadingTrait;
use Illuminate\Http\Request;
use app\Traits\TransferTrait;
use App\Models\DistributionCenter;
use Illuminate\Support\Facades\Date;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpKernel\HttpCache\ResponseCacheStrategy;

class Distribution_Center_controller extends Controller
{
    use TransferTrait;
    use LoadingTrait;
    public function show_my_suppurted_products(Request $request)
    {
         

        
        $token = $request->bearerToken();


        $payload = JWTAuth::getPayload($token);


        $manager = JWTAuth::parseToken()->authenticate('employe');
        $manager = Employe::find($payload->get("id"));


        //place is a warehouse or ditribution center
        $place = $manager->workable;

        $public_details = $place->public_details_about_products;

        $i = 0;
        $my_products = [];
        foreach ($public_details as $details) {

            $product = $details->product->only(["name", "description", "img_path"]);

            $product["actual_load"] = $this->calculate_actual_load($details->product_details);

            $product["average"] = $details->average;
            //we most store the variance devided into n or devide it when we will use it
            $product["deviation"] = sqrt($details->variance);

            $my_products[$i] = $product;
            $i++;
        }
        return response()->json(["msg" => "i am happy", "your_products" => $my_products], 202);
    }

    public function ask_product(Request $request)
    {
        $validated_values = $request->validate([
            "product_id" => "required|integer",
            "requested_quantity" => "required|numeric",
        ]);
        $token = $request->bearerToken();

        $payload = JWTAuth::getPayload($token);

        $manager = JWTAuth::parseToken()->authenticate('employe');

        $manager = Employe::find($payload->get("id"));

        //place is a warehouse or ditribution center

        $place = $manager->workable;
    }


}
