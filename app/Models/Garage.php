<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    use HasFactory;
    protected $guarded;

        public function vehicles()
    {
        return $this->hasMany(Vehicle::class,"garage_id");
    }

       public function existable()
    {
        return $this->morphTo();
    }





}

