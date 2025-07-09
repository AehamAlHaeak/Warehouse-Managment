<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Positions_on_sto_m extends Model
{
    use HasFactory;
    protected $guarded;
    public $table="positions_on_sto_m";
    public function container(){
        return $this->belongsTo(Import_op_container::class,"imp_op_contin_id");
    }
    public function storage_element(){
        return $this->belongsTo(Import_op_storage_md::class,"imp_op_stor_id");
    }
    
    
}



