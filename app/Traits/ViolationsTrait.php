<?php

namespace App\Traits;

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
}
