<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request_detail extends Model
{
    use HasFactory;
    protected $guarded;
    public function parent_load()
    {
      return $this->belongsTo(Imp_continer_product::class,"imp_cont_prod_id");
    }
}
