<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Import_operation_product extends Model
{

    protected $guarded = [];
    protected $table = 'import_operation_product';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    
    public function supllier(){
        return $this->belongsTo(Supplier::class,"supplier_id");
    }

    public function loads(){
        return $this->hasMany(Imp_continer_product::class,"imp_op_product_id");
    }
    public function parent_product(){
        return $this->belongsTo(Product::class,"product_id");
    }
}
