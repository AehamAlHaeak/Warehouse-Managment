<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;
    protected $guarded;
    // protected $fillable=[ 'import_operation_id', 'latitude', 'longitude', 'location', 'type', 'garage_id', 'warehouse_id'];

    

    public function vehicleTransfer()
    {
        return $this->belongsToMany(Transfer::class, 'transfer__vehicle');
    }

    

    


    public function import_operation()
    {
        return $this->belongsTo(Import_operation::class);
    }
    public function transfer_products()
    {
        return $this->belongsTo(Product::class, "product_id");
    }


    
    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }
     public function actual_transfer(){
        return $this->belongsTo(Transfer::class,"transfer_id");
     }
    public function driver(){
        return $this->belongsTo(Employe::class,"driver_id");
    }
      
    public function  product(){
        return $this->belongsTo(Product::class,"product_id");
    }
}
