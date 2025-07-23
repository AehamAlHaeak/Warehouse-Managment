<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMovable extends Model
{
    use HasFactory;
    protected $guarded = [];   
    protected $casts = [
        'destination' => 'array',
        'transfer_time_starts' => 'datetime',
        'shipment_delivery_time' => 'datetime',
    ];
    public function distributionCenter()
    {
        return $this->belongsTo(DistributionCenter::class);
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    } 
    public function products()
    {
        return $this->belongsToMany(Product::class, 'movable_product')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }
    
}
