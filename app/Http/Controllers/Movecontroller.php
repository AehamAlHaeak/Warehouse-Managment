<?php

namespace App\Http\Controllers;

use App\Models\Containers_type;
use App\Models\Storage_media;
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
'date_of_finished' =>  $transferDetail->tranfer->date_of_finished
        ]);
    }
}
