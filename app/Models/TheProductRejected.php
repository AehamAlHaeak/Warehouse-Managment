<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TheProductRejected extends Model
{
    use HasFactory;
     protected $guarded=[];
    public function Import_operation_product(){
        return $this->belongsToMany(Import_operation_product::class,'impo_ope_prod_id');
    }

}
