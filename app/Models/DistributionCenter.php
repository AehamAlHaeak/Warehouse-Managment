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

    public function Products(){
    return $this->hasMany(distribution_center_Product::class,"distribution_center_id");
    }


    public function employes(){
        return $this->morphMany(Employe::class,"workable");
    }
}