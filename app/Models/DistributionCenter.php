<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistributionCenter extends Model
{
    use HasFactory;
    protected $guarded;

    protected $appends = ['type_name'];

    public function getTypeNameAttribute()
    {
        $type_name = $this->type;
        unset($this["type"]);
        return $type_name?->name ?? 'Unknown';
    }



    public function employees()
    {
        return $this->morphMany(Employe::class, "workable");
    }



    public function sections()
    {
        return $this->morphMany(Section::class, "existable");
    }

    public function type()
    {
        return $this->belongsTo(type::class, "type_id");
    }

    public function garages()
    {
        return $this->morphMany(Garage::class, "existable");
    }
    public function resived_transfers()
    {
        return $this->morphMany(Transfer::class, "destinationable");
    }
    public function sent_transfers()
    {
        return $this->morphMany(Transfer::class, "sourceable");
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, "warehouse_id");
    }
}
