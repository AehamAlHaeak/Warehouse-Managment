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
    public function distribution_centers(){
        return $this->hasMany(DistributionCenter::class,"type_id");
    }
    public function products(){
        return $this->hasMany(Product::class,"type_id");
    }
}
