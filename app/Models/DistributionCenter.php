<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistributionCenter extends Model
{
    use HasFactory;
    protected $guarded;

    public function bill(){
        return $this->hasMany(Bill::class);
    }
    
    

    public function employes(){
        return $this->morphMany(Employe::class,"workable");
    }
    
    public function supported_product(){
        return $this->belongsToMany(product::class,'distribution_center__product');
    }

    public function sections(){
       return $this->morphMany(Section::class,"existable");
    }

    public function type() {
        return $this->belongsTo(type::class,"type_id");
    }
}