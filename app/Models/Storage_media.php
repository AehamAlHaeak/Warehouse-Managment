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
 
   
    

     public function supplier(){
      return $this->morphToMany(Supplier::class,"suppliesable","supplier__details")
                 ->withPivot('max_delivery_time_by_days')
                ->as('details');
    }

    public function imported_storage_elements(){
        return $this->hasMany(Import_op_storage_md::class,"storage_media_id");
    } 
}
