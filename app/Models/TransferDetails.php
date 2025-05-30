<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferDetails extends Model
{
    use HasFactory;
    protected $guarded=[];
  public function tranfer(){
    return $this->belongsToMany(Transfer::class,'transfer_id');
  }
  
      public function vehicle()
      {
          return $this->belongsTo(Vehicle::class);
      }
  }
   

