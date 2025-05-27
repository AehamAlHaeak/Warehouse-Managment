<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;
    protected $guarded;
    public function posetions(){
        return $this->hasMany(Posetions_on_section::class,"section_id");
    }

    public function storage_elements(){
return $this->belongsToMany(Import_op_storage_md::class,"posetions_on_sections","section_id","storage_media_id");
    }

    public function product(){

        return $this->belongsTo(Product::class,"product_id");
    }
    public function existable(){
        return $this->morphTo();
    }
}
