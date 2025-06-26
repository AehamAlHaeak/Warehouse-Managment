<?php

namespace App\Models;

use App\Traits\AlgorithmsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    use AlgorithmsTrait;
    protected $guarded;

    

    public function favoriteByUser(){
        return $this->belongsToMany(User::class,'favorites');
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
   
    public function container(){
        return $this->hasOne(Containers_type::class,"product_id");
    }
    public function sections(){
        return $this->hasMany(Section::class,"product_id");
    }
   
    public function storage_media(){
        return $this->hasOne(Storage_media::class,"product_id");
    }

}
