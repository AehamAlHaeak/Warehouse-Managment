<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class distribution_center_Product_details extends Pivot
{
    public function product(){
        return $this->belongsTo(Product::class,"product_id");
       }
}
