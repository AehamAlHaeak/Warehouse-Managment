<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
     protected $guarded;
     public function transfers(){
        return $this->hasMany(Transfer::class,"invoice_id");
     }
     public function user(){
      return $this->belongsTo(User::class,"user_id");
     }
    
}
