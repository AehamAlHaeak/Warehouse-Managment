<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Transfer_Vehicle extends Pivot
{
    //
    protected $guarded;

    protected $primaryKey = 'id';
    public $incrementing = true; 
    protected $keyType = 'int';  

}
