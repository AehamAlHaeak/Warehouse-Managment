<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Employe extends Model implements JWTSubject
{
    use HasFactory;
   
protected $guarded = []; 

    public function getJWTIdentifier()
    {
        return $this->getKey();  
    }

        public function getJWTCustomClaims()
    {
        return [];
    }
    public function getAuthIdentifierName()
    {
        return 'id'; 
    }
    public function specialization(){
        return $this->hasOne(Specialization::class);
    }
    public function workable(){
        return $this ->morphTo();
    }
}