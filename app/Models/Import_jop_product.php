<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Import_jop_product extends Pivot
{
    protected $guarded;
    public function products(){
        return $this->belongsTo(Product::class,"product_id");
    }
    public function supllier(){
        return $this->belongsTo(Supplier::class,"supplier_id");
    }
}
