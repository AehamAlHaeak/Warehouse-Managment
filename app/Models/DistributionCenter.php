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

    public function distributionCenterProduct(){
        return $this->belongsToMany(Product::class,'distribution_center__product');
    }

    public function distributionCenterType(){
        return $this->hasOne(type::class);
    }
}
