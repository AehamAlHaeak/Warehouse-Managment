<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Import_op_req extends Pivot
{
    use HasFactory;
    protected $guarded;
    protected $primaryKey = 'id'; 
    public $incrementing = true; 
    protected $keyType = 'int';  

}
