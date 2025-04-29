<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class distribution_center_Product extends Pivot
{
    //
    protected $guarded;

    public function product_distribution_center(){
     return $this->belongsTo(DistributionCenter::class,'distribution_center_id');
    }

    public function product_details(){
        return $this->hasMany(distribution_center_Product_details::class,"dist_center_prod_id");
    }
}
