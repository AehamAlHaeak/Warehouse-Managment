<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    protected $guarded;

   public function supplier_products()
{
   
    return $this->morphedByMany(Product::class, 'suppliesable', 'supplier__details')
                ->withPivot('max_delivery_time_by_days')->
                as("details");
                
}

public function supplier_storage_media()
{
    return $this->morphedByMany(Storage_media::class, 'suppliesable', 'supplier__details')
                ->withPivot('max_delivery_time_by_days')
                ->as('details');
}
  
public function import_operations(){
    return $this->hasMany(Import_operation::class,"supplier_id");
}
}
