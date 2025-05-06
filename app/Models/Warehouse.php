<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;
    protected $guarded;

    public function supported_roduct(){
        return $this->belongsToMany(product::class,'warehouse__product');
    }

    public function wareHouseType(){
        return $this->belongsTo(type::class);
    }


    public function public_details_about_products(){
        return $this->hasMany(Warehouse_Product::class);
    }
    public function employees(){
    return $this->morphMany(Employe::class,"workable");
    }

}
