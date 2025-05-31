<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Import_op_storage_md extends Model
{
    use HasFactory;
    protected $guarded;
    protected $primaryKey = 'id'; 
    public $incrementing = true; 
    protected $keyType = 'int';  
       protected $table = 'import_op_storage_md';
      
    public function impo_container(){
        return $this->belongsToMany(Import_op_container::class,"imp_op_conti_id");
    }
    public function parent_storage_media(){
        return $this->belongsTo(Storage_media::class,"storage_media_id");
    }
    public function posetions(){

        return $this->hasMany(Positions_on_sto_m::class,"imp_op_stor_id");
    }
}
