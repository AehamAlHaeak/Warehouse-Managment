<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Warehouse_Product_details extends Pivot
{  
  public $table="warehouse_product_details";
   protected $guarded;
  public function all_details(){
   return $this->belongsTo(Import_jop_product::class,"import_jop_product_id");
  }
   
}
