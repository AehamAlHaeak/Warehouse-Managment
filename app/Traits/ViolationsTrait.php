<?php

namespace App\Traits;

use App\Models\Job;
use App\Models\Violation;
use App\Jobs\TempViolation;
use Illuminate\Support\Facades\Queue;

trait ViolationsTrait
{
    public function reset_conditions_on_object($object){
       $product=$object->product;
        
         $temp=( $product->highest_temperature+$product->lowest_temperature)/2;
         $humidity=( $product->highest_humidity+$product->lowest_humidity)/2;
         $light=( $product->highest_light+$product->lowest_light)/2;
         $pressure=( $product->highest_pressure+$product->lowest_pressure)/2;   
         $ventilation=( $product->highest_ventilation+$product->lowest_ventilation)/2;

         $object->internal_temperature=$temp;
         $object->internal_humidity=$humidity;
         $object->internal_light=$light;
         $object->internal_pressure=$pressure;
         $object->internal_ventilation=$ventilation;
         $object->save();
        return $object;
      
    }

    public function deal_with_variable_conditions($place,$parameter,$value){
          
           
            $place->update(["internal_".$parameter=>$value]);
            
            $activ_violation = $place->violations()->where("parameter",$parameter)->where("job_id", "!=", null)->first();
            $job = null;
            if ($activ_violation) {
                $job = Job::find($activ_violation->job_id);
            }
            if (
                $value < $place->product["highest_".$parameter]
                && $value > $place->product["lowest_".$parameter]
            ) {
                if ($job) {
                    $job->delete($job->id);
                    $activ_violation->job_id = null;
                    $activ_violation->status = "handled";
                    $activ_violation->save();
                }
            } else {
                if (!$job) {
                    $violation = Violation::create(["parameter" => $parameter, "violable_id" => $place->id, "violable_type" => get_class($place), "status" => "wait"]);
                    $job = new TempViolation($violation->id);
                    $jobId = Queue::later(now()->addMinutes(0), $job);
                    $violation->job_id = $jobId;
                    $violation->save();
                }
            }
         return "dun";
    }
}
