<?php

namespace App\Traits;

use App\Models\Bill;
use App\Models\DistributionCenter;
use App\Models\Transfer;
use App\Models\User;

trait TransferTrait
{

    public function transfers($object, $object2, $details, $date_of_resiving)
    {



        if ($object instanceof DistributionCenter && $object2 instanceof User) {

            

            $bill = new Bill();

            $bill->user_id = $object2->id;

            $bill->distribution_center_id = $object->id;

            $bill->date_of_resiving = $date_of_resiving;

            $bill->location = $object->location;

            $bill->latitude = $object->latitude;

            $bill->longitude = $object->longitude;

            $bill->save();

            return $bill;
        }

        $transfer = new Transfer();
        $transfer->sourceable_type = get_class($object);
        $transfer->sourceable_id= $object->id;
        $transfer->destinationable_type=get_class($object2);
        $transfer->destinationable_id= $object2->id;
        $transfer->date_of_resiving= $date_of_resiving;
        $transfer->save();
        return $transfer;

    }




}
