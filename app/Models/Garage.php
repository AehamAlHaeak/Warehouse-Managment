<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    use HasFactory;
    protected $guarded;

    //  protected $fillable = ['name', 'available_space', 'vehicle_type', 'warehouse_id'];

        public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class);
    }

        public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }





}
