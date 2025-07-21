<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class reserved_details extends Model
{
 protected $guarded;
   protected $primaryKey = 'id'; 
    public $incrementing = true; 
    protected $keyType = 'int';
    public $table="reserved_detail";  
    public function parent_load()
    {
      return $this->belongsTo(Imp_continer_product::class,"imp_cont_prod_id");
    }
}
