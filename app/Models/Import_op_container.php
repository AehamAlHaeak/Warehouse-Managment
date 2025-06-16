<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Import_op_container extends Model
{
   use HasFactory;
   protected $guarded;

   protected $primaryKey = 'id';
   public $incrementing = true;
   protected $keyType = 'int';
   protected $table = 'import_op_containers';
   public function impo_storage_md()
   {
      return $this->belongsToMany(Import_op_storage_md::class, "imp_op_stor_id");
   }
   public function parent_continer()
   {
      return $this->belongsTo(Containers_type::class, "container_type_id");
   }
   public function import_operation()
   {
      return $this->belongsTo(Import_operation::class, "import_operation_id");
   }
   public function imp_op_product()
   {
      return $this->belongsToMany(
         Import_operation_product::class,
         "imp_continer_products",
         "imp_op_cont_id",
         "imp_op_product_id"
      );
   }
}
