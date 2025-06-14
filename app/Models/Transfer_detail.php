<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer_detail extends Model
{
    //
    protected $guarded;

    protected $primaryKey = 'id';
    public $incrementing = true; 
    protected $keyType = 'int';
    protected $table='transfer__details';  
    public function continers(){
        return $this->belongsToMany(Import_op_container::class,"continer_transfers","transfer_detail_id","imp_op_contin_id");
    }
}
