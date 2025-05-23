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
     public function showEmployees($id)
    {
        $distemployee = DistributionCenter::with('employees')->findOrFail($id);

        return response()->json([
            'DistributionCenter' =>  $distemployee->name,
        ]);
    }


        public function showprod_In_Dist($id)
    {
        $dist_prod = DistributionCenter::find($id)->supported_product;
        return  $dist_prod;
    }


     public function showSections($id)
    {
        $dist_section =DistributionCenter::with('sections')->findOrFail($id);

        return response()->json([
            'DistributionCenter' => $dist_section->name,

        ]);
    }


     public function showType($id)
    {
        $dist_type= DistributionCenter::with('type')->findOrFail($id);

        return response()->json([
            'DistributionCenter' => $dist_type->name,
            'type' => $dist_type->type,
        ]);
    }


    












}
