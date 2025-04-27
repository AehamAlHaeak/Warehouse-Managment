<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;
    protected $guarded;

    public function wareHouseProduct(){
        return $this->belongsToMany(product::class,'warehouse__product');
    }

    public function wareHouseType(){
        return $this->hasOne(type::class);
    }


}
