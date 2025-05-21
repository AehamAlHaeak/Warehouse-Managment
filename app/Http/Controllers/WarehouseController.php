<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class WarehouseController extends Controller
{
    public function showGarage($id){
        $garage=Warehouse::find($id)->garages;
        return $garage;
    }

    public function showVehicles_OnGarage($garageid){
        
    }
}