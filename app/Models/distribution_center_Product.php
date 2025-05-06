<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Distribution_center_Product extends Pivot
{
    //
    protected $guarded;

    public function product(){
        return $this->belongsTo(Product::class,"product_id");
    }

    public function product_details(){
        return $this->hasMany(Distribution_center_Product_details::class,"dist_center_prod_id");
    }
}
