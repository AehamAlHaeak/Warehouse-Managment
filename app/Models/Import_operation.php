<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import_operation extends Model
{
    use HasFactory;
    protected $guarded;
    public function products_details(){
        return $this->hasMany(Import_operation_product::class);
    }
    public function vehicles(){
        return $this->hasMany(Import_operation_product::class);
    }
    public function cargos(){
        return $this->hasMany(Import_operation_product::class);
    }
    public function Supplier(){
        return $this->belongsTo(Supplier::class,"supplier_id");
    }

    public function containers(){
        return $this->hasMany(Import_op_container::class,"import_operation_id");
    }

    public function storage_md(){
        return $this->belongsToMany(Storage_media::class,"storage_media_id");
    }
}
