<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    protected $guarded;

    // public function bill(){
    //     return $this->hasMany(Bill::class);
    // }

    public function favoriteByUser(){
        return $this->belongsToMany(User::class,'favorites');
    }

    

    

    public function productTransfer(){
        return $this->belongsToMany(Transfer::class,'transfer__vehicle');
    }

    public function productType(){
        return $this->belongsTo(type::class);
    }


public function import_operation_details(){
    return $this->hasMany(Import_operation_product::class,"product_id");
}


public function userMovables()
{
    return $this->belongsToMany(UserMovable::class, 'movable_product')
                ->withPivot('quantity')
                ->withTimestamps();
}



public function type() {
        return $this->belongsTo(type::class,"type_id");
    }

    public function supplier(){
      return $this->morphToMany(Supplier::class,"suppliesable","supplier__details")
                 ->withPivot('max_delivery_time_by_days')
                ->as('details');
    }
   


}
