<?php

namespace App\Http\Controllers;

use App\Models\DistributionCenter;
use App\Models\UserMovable;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class UserMovableController extends Controller
{
    public function userTransfers(Request $request){
        $user=auth()->user();
        $validated = $request->validate([
            'distribution_center_id' => 'required|exists:distribution_centers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'locationName'=>'required|string',
            'latitude'=>'required|numeric',
            'longitude'=>'required|numeric',
            'transfer_time_starts' => 'required|date',
            'shipment_delivery_time' => 'nullable|date',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);
        $userMovable=new UserMovable();
        $userMovable->user_id=$user->id;
        $userMovable->distribution_center_id=$request->distribution_center_id;
        //$userMovable->vehicle_id=$request->vehicle_id;
        $userMovable->destination=[
'locationName'=>$request->locationName,
'latitude'=>$request->latitude,
'longitude'=> $request->longitude
        ];
$userMovable->transfer_time_starts=$request->transfer_time_starts;
$userMovable->shipment_delivery_time = $validated['shipment_delivery_time'] ?? null;
$userMovable->save();
foreach($validated['products'] as $product){
$userMovable->products()->attach([$product['product_id'],
'quantity'=>$product['quantity'],
]);
}
$vehicle=new vehicle;


foreach($userMovable->products as $product){
    $vehicle=Vehicle::find($product->type);
    if(!$vehicle){
        return response()->json(['msg'=>'the product type does not match the vehicle type'],422);
    }else{

    if($vehicle->status=='under_work'){
        return response()->json(['msg'=>'the vehicles are occupied '],400);
        }
elseif($vehicle->status== 'finished'){
    $totalLoad = 0;
foreach($userMovable->products as $product) {
$totalLoad+=$product->weight* $product->Pivot->quantity;}
}
$userMovable->maxload= $totalLoad;
if($userMovable->maxload<=$vehicle->maxload){
    $userMovable->vehicle_id=$request->vehicle_id;
    $userMovable->save();
    $vehicle->status='under_work';

    return response()->json(['msg'=> 'the shipment has been sent'],200);
}
}
}   
}
}

