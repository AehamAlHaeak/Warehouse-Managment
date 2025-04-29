<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Import_jop_product extends Pivot
{
    public function products(){
        return $this->belongsTo(Product::class,"product_id");
    }
}
