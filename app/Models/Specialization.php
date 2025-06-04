<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specialization extends Model
{
    use HasFactory;
    protected $guarded;
    public function employees(){
        return $this->hasMany(Employe::class,"specialization_id");
    }
}
