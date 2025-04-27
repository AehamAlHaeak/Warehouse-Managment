<?php

namespace App\Traits;
use Illuminate\Support\Facades\Schema;
use App\Models\Bill;
use App\Models\Cargo;
use App\Models\Employe;
use App\Models\DistributionCenter;
use App\Models\Bill_Detail;
use App\Models\distribution_center_Product;
use App\Models\Favorite;
use App\Models\Garage;
use App\Models\Product;
use App\Models\Specialization;
use App\Models\Supplier;
use App\Models\Supplier_Product;
use App\Models\Transfer;
use App\Models\Transfer_Vehicle;
use App\Models\Werehouse_Product;
use App\Models\type;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
trait CRUDTrait
{
public function create_item($model,$data){
    $table = (new $model)->getTable();
// to tknow the columns in the table use this thin we filter the data array
    $columns = Schema::getColumnListing($table);

  //filter data to remove the unwanted data depending on the columns in the table
    $filtered_data = array_filter(
        $data,
        fn($key) => in_array($key, $columns),
        ARRAY_FILTER_USE_KEY
    );

    $item = $model::create($filtered_data);
    return $item;
}
}
