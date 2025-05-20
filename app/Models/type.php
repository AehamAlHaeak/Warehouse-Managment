<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class type extends Model
{
    use HasFactory;
    protected $guarded;

    public function warehouses(){
        return $this->hasMany(Warehouse::class,"type_id");
    }
}
