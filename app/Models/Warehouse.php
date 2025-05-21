<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;
    protected $guarded;

    //    protected $fillable = [ 'name', 'location', 'capacity'];



    public function supported_roduct(){
        return $this->belongsToMany(product::class,'warehouse__product');
    }

    public function wareHouseType(){
        return $this->belongsTo(type::class);
    }



    public function employees(){
    return $this->morphMany(Employe::class,"workable");
    }

    public function sections(){
       return $this->morphMany(Section::class,"existable");
    }

    public function type() {
        return $this->belongsTo(type::class,"type_id");
    }

        public function garages()
    {
        return $this->hasOne(Garage::class);
    }

        public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

}
