<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Job;
use App\Models\Violation;
use App\Jobs\TempViolation;
use Illuminate\Support\Facades\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Traits\AlgorithmsTrait;
use App\Traits\ViolationsTrait;
use App\Models\Import_op_container;
use App\Models\Import_op_storage_md;

class ViolationController extends Controller
{
    use AlgorithmsTrait;
    use ViolationsTrait;
    
    public function set_conditions(Request $request)
    {
         
        try {
            try {
                $validated_values = $request->validate([
                    "parameter"=>"required|in:temperature,humidity,light,pressure,ventilation",
                    "value" => "required",
                    "place_id" => "required|integer",
                    "place_type" => "required|in:Vehicle,Import_op_storage_md"
                ]);
            }  catch (ValidationException $e) {
                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
               $model_place = "App\\Models\\" . $validated_values["place_type"];
            $place = $model_place::find($validated_values["place_id"]);
            if (!$place) {
                return response()->json(["msg" => "the place which you want is not exist"], 404);
            }
             $output=$this->deal_with_variable_conditions($place, $validated_values["parameter"], $validated_values["value"]);
            return response()->json(["msg" => "done"], 200);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()], 409);
        }
    }
}
