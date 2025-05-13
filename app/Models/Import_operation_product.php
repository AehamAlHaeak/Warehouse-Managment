<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Import_operation_product extends Pivot
{
    protected $guarded;
    public function products(){
        return $this->belongsTo(Product::class,"product_id");
    }
    public function supllier(){
        return $this->belongsTo(Supplier::class,"supplier_id");
    }

    public function container(){

        return $this->belongsTo(import_op_container::class,"imp_op_contin_id");
    }
}
