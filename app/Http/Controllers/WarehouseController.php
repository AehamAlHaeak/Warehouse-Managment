<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class WarehouseController extends Controller
{
    public function showGarage($id)
    {
        $garage = Warehouse::find($id)->garages;
        return $garage;
    }

    public function showVehicles_OnGarage($garageid)
    {
        $vehicle = Vehicle::find($garageid)->vehicles;
        return $vehicle;
    }

    public function showprod_In_Warehouse($id)
    {
        $ware_prod = Warehouse::find($id)->supported_roduct;
        return  $ware_prod;
    }

    public function show_Storage_Md($id)
    {

        $warehouse = Warehouse::with('sections.posetions.storage_element.parent_storage_media')->findOrFail($id);

        $storageMedias = collect();

        foreach ($warehouse->sections as $section) {
            foreach ($section->posetions as $position) {
                if ($position->storage_element && $position->storage_element->parent_storage_media) {
                    $storageMedias->push($position->storage_element->parent_storage_media);
                }
            }
        }


        $storageMedias = $storageMedias->unique('id')->values();


        return response()->json([
            'warehouse' => $warehouse->only('id', 'name', 'location'),
             
        ]);
    }

    public function showEmployees($id)
    {
        $warehouse = Warehouse::with('employees')->findOrFail($id);

        return response()->json([
            'warehouse' => $warehouse->name,

        ]);
    }

    public function showType($id)
    {
        $warehouse = Warehouse::with('type')->findOrFail($id);

        return response()->json([
            'warehouse' => $warehouse->name,
            'type' => $warehouse->type,
        ]);
    }

    public function showSections($id)
    {
        $warehouse = Warehouse::with('sections')->findOrFail($id);

        return response()->json([
            'warehouse' => $warehouse->name,

        ]);
    }
}
