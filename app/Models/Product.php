<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function productWareHouse(){
        return $this->belongsToMany(Warehouse::class,'warehouse__product');
    }

    public function productVehicle(){
        return $this->belongsToMany(Vehicle::class,'transfer__vehicle');
    }

    public function productTransfer(){
        return $this->belongsToMany(Transfer::class,'transfer__vehicle');
    }

    public function productType(){
        return $this->belongsTo(type::class);
    }

public function importing_details(){
    return $this-> hasMany(Supplier_Product::class,'product_id');

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

}
