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

}
