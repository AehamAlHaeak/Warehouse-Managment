<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Warehouse_Product extends Pivot
{
    
    protected $guarded;
    public function product_details(){
        return $this->hasMany(Warehouse_Product_details::class);
    }
    public function product(){
        return $this->belongsTo(Product::class,"product_id");
    }
   
}
