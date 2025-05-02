<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import_jop extends Model
{
    use HasFactory;
    protected $guarded;
    public function products_details(){
        return $this->hasMany(Import_jop_product::class);
    }
    public function vehicles(){
        return $this->hasMany(Import_jop_product::class);
    }
    public function cargos(){
        return $this->hasMany(Import_jop_product::class);
    }
    public function Supplier(){
        return $this->belongsTo(Supplier::class,"supplier_id");
    }
}
