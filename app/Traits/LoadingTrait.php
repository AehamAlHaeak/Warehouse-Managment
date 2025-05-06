<?php

namespace App\Traits;
use App\Models\Bill;
use App\Models\type;


use App\Models\Garage;
use App\Models\Employe;
use App\Models\Product;
use App\Models\Vehicle;
use App\Models\Favorite;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\Warehouse;
use App\Models\Bill_Detail;
use Illuminate\Http\Request;
use App\Models\Specialization;
use App\Models\Supplier_Product;
use App\Models\Transfer_Vehicle;
use App\Models\Werehouse_Product;
use App\Models\DistributionCenter;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use App\Models\Distribution_center_Product;
trait LoadingTrait
{   

    public function calculate_actual_load($product_details){
       $actual_load=0;
       foreach($product_details as $item){
        $actual_load+=$item->actual_load;
       }
   return $actual_load;
}

   




//    public function my_products($model){
//      $supported_products=$model->supportes_products;
//      $my_products=[];
//      $i=0;
//      foreach($supported_products as $suppurted_product){
//        $my_products[$i]["product"]=$suppurted_product->product;
//        //tin the code i fetch the quantity and details on the warehouse the important is actual_load 
//        $details=$suppurted_product->product_details;
//        $my_products[$i]["product"]->actual_load=$this->calculate_quantity($details);
//        $details=$details->all_details;
//        $my_products[$i]["product"]->details=$details;
       
//      }
//      return $my_products;

//    }




}
