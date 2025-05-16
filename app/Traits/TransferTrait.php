<?php


namespace App\Traits;

use App\Models\Import_jop_product;
use App\Models\Import_operation_product;
use App\Models\Transfer;
use App\Models\TransferDetails;
use App\Models\Vehicle;
use Carbon\Carbon;
trait TransferTrait
{

    public function transfer($object1, $object2, $detail, $date_of_resiving)
    {
        if (!($object1 instanceof DistributionCenter && $object2 instanceof User)) {
            return;
        }

        $transfer = new Transfer();
        $transfer->sourceable_type = get_class($object1);
        $transfer->sourceable_id = $object1->id;
        $transfer->destinationable_type = get_class($object2);
        $transfer->destinationable_id = $object2->id;
        $transfer->date_of_resiving = $date_of_resiving;
        $transfer->save();

        foreach ($detail as $importJopId => $quantity) {
            $importJopProduct = Import_operation_product::find($importJopId);
            if (!$importJopProduct || !$importJopProduct->products) {
                continue;
            }

            $product = $importJopProduct->products;
            $productTypeId = $product->type_id;

            // جلب الشاحنات المتاحة فقط
            $vehicles = Vehicle::where('status', true)->get();

            foreach ($vehicles as $vehicle) {
                if ($vehicle->type_id == $productTypeId) {
                    $quantity = $this->checkLoad($vehicle, $quantity, $transfer->id);

                    if ($quantity <= 0) {
                        break;
                    }
                }
            }

            if ($quantity > 0) {

                echo "⚠️ don't find vehicle: $quantity\n";
                $busyVehicles = Vehicle::where('type_id', $productTypeId)
                    ->where('status', false)
                    ->get();

                if ($busyVehicles->isNotEmpty()) {

                    $selectedVehicle = $busyVehicles->sortBy('expected_finish_time')->first();
                }
                if ($busyVehicles->isNotEmpty()) {

                    $selectedVehicle = $busyVehicles->sortBy('expected_finish_time')->first();

                    if ($selectedVehicle) {

                        $transferDetail = new TransferDetails();
                        $transferDetail->vehicle_id = $selectedVehicle->id;
                        $transferDetail->transfer_id = $transfer->id;
                        $transferDetail->quantity_by_kg = $quantity;
                        $transferDetail->status = "wait";
                        $transferDetail->save();

                        echo "✅ the vehicle reserved successfully{$selectedVehicle->id} بانتظار توفرها\n";
                    }
                } else {
                    echo "🚫 There are no trucks to transport the remaining quantity: $quantity\n";
                }

            }
        }
    }


    public function checkLoad($vehicle, $quantity, $transfer_id)
    {
        $transferDetail = new TransferDetails();
        $minutesPerKg = 5.00;


        if ($quantity <= $vehicle->max_load) {
            $vehicle->load = $quantity;


            $transferDetail->vehicle_id = $vehicle->id;
            $transferDetail->transfer_id = $transfer_id;
            $transferDetail->quantity_by_kg = $vehicle->load;
            $transferDetail->status = "under_work";


            $estimatedMinutes = $vehicle->load * $minutesPerKg;
            $expectedFinish = now()->addMinutes($estimatedMinutes);
            $transferDetail->expected_finish_time = $expectedFinish;

            $transferDetail->save();
            $vehicle->save();

            return 0;
        }


        $vehicle->load = $vehicle->max_load;


        $transferDetail->vehicle_id = $vehicle->id;
        $transferDetail->transfer_id = $transfer_id;
        $transferDetail->quantity_by_kg = $vehicle->load;
        $transferDetail->status = "under_work";

        $estimatedMinutes = $vehicle->load * $minutesPerKg;
        $expectedFinish = now()->addMinutes($estimatedMinutes);
        $transferDetail->expected_finish_time = $expectedFinish;

        $transferDetail->save();
        $vehicle->save();

        return $quantity - $vehicle->max_load;
    }
    public function markAsReceived()
    {
        $this->status = 'received';
        if ($this->transfer) {
            $this->date_of_finished = Carbon::now();
            $this->save();
        }
        if ($this->vehicle) {
            $this->status = false;
            $this->save();
        }
    }
}
