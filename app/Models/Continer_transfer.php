<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Continer_transfer extends Model
{
    use HasFactory;
    protected $guarded;
    protected $hidden = ['pivot'];
}
