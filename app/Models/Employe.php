<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employe extends Model
{
    use HasFactory;
    protected $guarded;

    public function specialization(){
        return $this->belongsTo(Specialization::class,"specialization_id");
    }
    public function workable(){
        return $this ->morphTo();
    }
}