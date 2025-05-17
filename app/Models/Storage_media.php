<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Storage_media extends Model
{
    use HasFactory;
    protected $guarded;

    public function container(){
        return $this->belongsTo(Containers_type::class);
    }
 
    public function impo_operation(){
        return $this->belongsToMany(Import_operation::class,"Import_operation_id");
    }
}
