<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;
    protected $guarded;

    public function bill(){
        return $this->hasMany(Bill::class);
    }

    public function vehicleTransfer(){
        return $this->belongsToMany(Transfer::class,'transfer__vehicle');
    }

    public function vehicleProduct(){
        return $this->belongsToMany(Product::class,'transfer__vehicle');
    }

    public function vehicleType(){
        return $this->hasOne(type::class);
    }

    public function vehicleCargos(){
        return $this->hasMany(Cargo::class);
    }

}
