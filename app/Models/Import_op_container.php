<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Import_op_container extends Pivot
{
    use HasFactory;
    protected $guarded;
     
    protected $primaryKey = 'id';
    public $incrementing = true; 
    protected $keyType = 'int';  
    protected $table = 'import_op_containers';
     public function impo_storage_md(){
        return $this->belongsToMany(Import_op_storage_md::class,"imp_op_stor_id");
     }

}
