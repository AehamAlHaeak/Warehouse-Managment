<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;
    protected $guarded;
    // protected $fillable=[ 'import_operation_id', 'latitude', 'longitude', 'location', 'type', 'garage_id', 'warehouse_id'];


    public function bill()
    {
        return $this->hasMany(Bill::class);
    }

    public function vehicleTransfer()
    {
        return $this->belongsToMany(Transfer::class, 'transfer__vehicle');
    }

    public function vehicleProduct()
    {
        return $this->belongsToMany(Product::class, 'transfer__vehicle');
    }

    public function vehicleType()
    {
        return $this->belongsTo(type::class, "type_id");
    }


    public function import_operation()
    {
        return $this->belongsTo(Import_operation::class);
    }
    public function transfer_products_of_type()
    {
        return $this->belongsTo(type::class, "type_id");
    }

    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
