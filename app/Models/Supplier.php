<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    protected $guarded;

    public function supplierProduct(){
        return $this->belongsToMany(Product::class,'supplier__product');
    }
    public function importing_details(){
        return $this-> hasMany(Supplier_Product::class,'supplier_id');
    
    
    }
}
