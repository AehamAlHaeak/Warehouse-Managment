<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class container_movments extends Model
{
    use HasFactory;
    protected $guarded;
    public function posetion_on_sto_m(){
        return $this->belongsTo(Positions_on_sto_m::class,"prev_position_id");
    }
}
