<?php

namespace App\Http\Controllers;

use App\Models\Containers_type;
use App\Models\Import_operation_product;
use App\Models\Storage_media;
use App\Models\TheProductRejected;
use App\Models\TransferDetails;
use App\Models\Vehicle;
use App\Traits\MoveTrait;
use App\Traits\TransferTrait;
use Illuminate\Http\Request;

class Movecontroller extends Controller
{
    use MoveTrait,TransferTrait;

    public function transferExample($vehicleId, $storage_md_id)
    {
        $source = Vehicle::find($vehicleId);
        $destination = Storage_media::find($storage_md_id);

        // gathering all the containers from the source
        $containers = Containers_type::where('source_type', get_class($source))
            ->where('source_id', $source->id)
            ->get();

        $this->transferContainers($containers, $source, $destination);

        return response()->json(['message' => 'تم نقل الحاويات بنجاح.']);
    }
    public function  confirmReception($id){
        $transferDetail=TransferDetails::find($id);
        $transferDetail->markAsReceived();
        return response()->json([
'message'=>'Receipt confirmed',
'date_of_finished' =>  $transferDetail->transfer->date_of_finished
        ]);
    }
    
    public function statusTheProduct(Request $request)
{
    $validated = $request->validate([
        'import_operation_id' => 'required|exists:import_operations,id',
        'product_id' => 'required|exists:products,id',
    ]);

    $productOperation = Import_operation_product::where('import_operation_id', $request->import_operation_id)
                        ->where('product_id', $request->product_id)
                        ->first();

    if (!$productOperation) {
        return response()->json(['message' => 'Product not found in import operation'], 404);
    }

    $productOperation->status = 'rejected';
    $productOperation->save(); 

    TheProductRejected::create([
        'impo_ope_prod_id' => $productOperation->id
    ]);

    return response()->json(['message' => 'Product rejected successfully'], 200);
}

}
