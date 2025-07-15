<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;
    protected $guarded;

    public function transferVehicle()
    {
        return $this->belongsToMany(Vehicle::class, 'tranfer__vehicle');
    }

    public function transferProduct()
    {
        return $this->belongsToMany(Product::class, 'tranfer__vehicle');
    }

    public function transfer_details()
    {
        return $this->hasMany(Transfer_detail::class, "transfer_id");
    }
    public function sourceable()
    {
        return $this->morphTo();
    }
    /*destinationable  sourceable*/
    public function destinationable()
    {
        return $this->morphTo();
    }
    public function next_transfer()
    {
        return $this->hasOne(Transfer::class, "parent_trans");
    }
    public function prev_transfer()
    {
        return $this->belongsTo(Transfer::class, "parent_trans");
    }
    public function contents(){
         return $this->transfer_details()->whereHas('continers')->exists();
    }
}
