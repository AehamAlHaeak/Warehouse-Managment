<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Containers_type extends Model
{

    use HasFactory;
    protected $guarded;

    public function container_type(){
        return $this->belongsTo(Product::class);
    }

    public function imp_operation(){
        return $this->belongsToMany (Import_operation::class,"import_operation_id");
    }
}
