<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Distribution_center_Product_details extends Pivot
{
    protected $guarded;
    protected $primaryKey = 'id';
    public $incrementing = true; 
    protected $keyType = 'int';  

    public function all_details(){
        return $this->belongsTo(Import_operation_product::class,"import_operation_product_id");
       }
}
