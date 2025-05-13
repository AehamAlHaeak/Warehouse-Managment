<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Import_op_storage_md extends Pivot
{
    use HasFactory;
    protected $guarded;

    public function impo_container(){
        return $this->belongsToMany(Import_op_container::class,"imp_op_conti_id");
    }
}
