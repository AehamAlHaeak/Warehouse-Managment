<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;
    protected $guarded;

    //    protected $fillable = [ 'name', 'location', 'capacity'];



   

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

   public function garages(){
    return $this->morphMany(Garage::class,"existable");
   }
       
public function distribution_centers(){
    return $this->hasMany(DistributionCenter::class,"warehouse_id");
}


      




}
