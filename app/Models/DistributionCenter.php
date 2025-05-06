<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistributionCenter extends Model
{
    use HasFactory;
    protected $guarded;

    public function bill(){
        return $this->hasMany(Bill::class);
    }
    
    public function public_details_about_products(){
    return $this->hasMany(Distribution_center_Product::class,"distribution_center_id");
    }


    public function employes(){
        return $this->morphMany(Employe::class,"workable");
    }
    public function supported_roduct(){
        return $this->belongsToMany(product::class,'distribution_center__product');
    }
}