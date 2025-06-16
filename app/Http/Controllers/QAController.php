<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Transfer;
use Illuminate\Http\Request;
use App\Models\Transfer_detail;
use App\Models\Import_op_container;
use App\Models\Imp_continer_product;

class QAController extends Controller
{
  public function show_actual_loads(Request $request)
  {
    try {
      $QA = $request->employe;
      $work_place = $QA->workable;
      $activ_transfers = $work_place->resived_transfers()->where("date_of_resiving", "!=", null)->where("date_of_finishing", null)->get();
      $in_QA_loads = collect();
      foreach ($activ_transfers as $transfer) {
        $transfer_details = $transfer->transfer_details;
        $in_QA_loads->push($transfer_details->where("status", "in_QA")->first());
      }


      if ($in_QA_loads->isEmpty()) {
        return response()->json(['msg' => 'No active transfers in QA'], 404);
      }
      return response()->json(["msg" => "here the loads", "lads" => $in_QA_loads], 200);
    } catch (Exception $e) {
      return response()->json(["msg" => $e->getMessage()], 500);
    }
  }
  public function show_load_details($load_id)
  {
    $load = Transfer_detail::find($load_id);
    $continers = $load->continers;
    if ($continers->isEmpty()) {
      return response()->json(['msg' => 'No containers in this load'], 404);
    }
    return response()->json(["msg" => "here the load details", "continers" => $load], 202);
  }

  public function show_container_details($load_id)
  {
    try {
      $continer = Import_op_container::find($load_id);
      if (!$continer) {
        return response()->json(['msg' => 'Container not found'], 404);
      }
      $contents = [];
      $imp_op_products = $continer->imp_op_product;
      $i = 0;
      foreach ($imp_op_products as $imp_op_product) {
        $contined_load = Imp_continer_product::where("imp_op_cont_id", $continer->id)->where("imp_op_product_id", $imp_op_product->id)->first();


        $imp_op_product->selled_load = $contined_load->sell_load->sum('load');
        $imp_op_product->reserved_load = $contined_load->reserved_load->sum('reserved_load');
        $imp_op_product->rejected_load = $contined_load->rejected_load->sum("rejected_load");
        $parent_product = $imp_op_product->parent_product;
        unset($imp_op_product["pivot"]);
        unset($imp_op_product["parent_product"]);
        $parent_product->setting_id = $contined_load->id;
        $parent_product->container_id = $continer->id;
        $contents[$i] = $parent_product;
        $parent_product->imp_op_product = $imp_op_product;
        $i++;
      }
      return response()->json(["msg" => "here the details", "product" => $contents], 202);
    } catch (Exception $e) {
      return response()->json(["msg" => $e->getMessage()], 500);
    }
  }
}
