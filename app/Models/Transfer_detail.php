<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer_detail extends Model
{
    //
    protected $guarded;
      protected $hidden = ['pivot'];
    protected $primaryKey = 'id';
    public $incrementing = true; 
    protected $keyType = 'int';
    protected $table='transfer__details';  
    public function continers(){
        return $this->belongsToMany(Import_op_container::class,"continer_transfers","transfer_detail_id","imp_op_contin_id");
    }
    public function transfer(){
        return $this->belongsTo(Transfer::class,"transfer_id");
    }
    public function vehicle(){
        return $this->belongsTo(Vehicle::class,"vehicle_id");
    }
   public function sell_loads(){
        return $this->hasMany(Sell_detail::class,"transfer_details_id");
     }
     public function reserved_loads(){
         return $this->hasMany(reserved_details::class,"transfer_details_id");
    }
}
