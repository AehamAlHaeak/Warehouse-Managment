<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Storage_media extends Model
{
    use HasFactory;
    protected $guarded;

    public function container(){
        return $this->belongsTo(Containers_type::class);
    }
 
    public function impo_operation(){
        return $this->belongsToMany(Import_operation::class,"Import_operation_id");
    }
     public function supplier(){
      return $this->morphToMany(Supplier::class,"suppliesable","supplier__details");
    }

    public function deliveryTime(): Attribute
{
    return Attribute::get(function () {
        
        return $this->pivot->max_delivery_time ?? null;
    });
}
}
