<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class distribution_center_Product extends Pivot
{
    //
    protected $guarded;

    public function productdistributionCenter(){
        return $this->belongsToMany(DistributionCenter::class,'distribution_center__product');
    }
}
