<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Import_op_storage_md extends Model
{
    use HasFactory;
    protected $guarded;
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $table = 'import_op_storage_md';

    public function impo_container()
    {
        return $this->belongsToMany(Import_op_container::class, "positions_on_sto_m", "imp_op_stor_id", "imp_op_contin_id");
    }
    public function parent_storage_media()
    {
        return $this->belongsTo(Storage_media::class, "storage_media_id");
    }
    public function posetions()
    {

        return $this->hasMany(Positions_on_sto_m::class, "imp_op_stor_id");
    }
    public function posetion_on_section()
    {
        return $this->hasOne(Posetions_on_section::class, "storage_media_id");
    }
    public function section()
    {
        return $this->belongsToMany(Section::class, "posetions_on_sections", "storage_media_id", "section_id");
    }
    public function continers()
    {
        return $this->belongsToMany(Import_op_container::class, "positions_on_sto_m", "imp_op_stor_id", "imp_op_contin_id");
    }
    public function getProductAttribute()
    {
        return $this->parent_storage_media?->product;
    }
     public function violations(){
       return $this->morphMany(Violation::class, "violable");  
    }
}
