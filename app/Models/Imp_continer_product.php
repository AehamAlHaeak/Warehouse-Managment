<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Imp_continer_product extends Model
{
    use HasFactory;
     protected $guarded;
     public function sell_load(){
        return $this->hasMany(Sell_detail::class,"imp_cont_prod_id");
     }
     public function reserved_load(){
         return $this->hasMany(reserved_details::class,"imp_cont_prod_id");
    }
    public function rejected_load(){
        return $this->hasMany(reject_details::class,"imp_cont_prod_id");
    }
  
}
