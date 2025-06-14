<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;
    protected $guarded;

    public function transferVehicle(){
        return $this->belongsToMany(Vehicle::class,'tranfer__vehicle');
    }

    public function transferProduct(){
         return $this->belongsToMany(Product::class,'tranfer__vehicle');
    }

    public function transfer_details(){
        return $this->hasMany(Transfer_detail::class,"transfer_id");
    }
    public function sourceable()
    {
        return $this->morphTo();
    }
    
    public function destinationable()
    {
        return $this->morphTo();
    }

}