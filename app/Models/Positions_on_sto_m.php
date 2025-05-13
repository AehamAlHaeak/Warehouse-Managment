<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Positions_on_sto_m extends Model
{
    use HasFactory;
    protected $guarded;


    public function requiredata(){
        return $this->belongsTo(type::class);
    }
}

}

