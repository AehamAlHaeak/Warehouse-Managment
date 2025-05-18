<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Supplier_Details extends Pivot
{
    //
    protected $guarded;
       
    protected $primaryKey = 'id';
    public $incrementing = true; 
    protected $keyType = 'int';  
    public $table="supplier__details";
    
}