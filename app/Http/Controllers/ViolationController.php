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
    public function set_temp(Request $request)
    {
        DB::beginTransaction();
        try {
            try {
                $validated_values = $request->validate([
                    "temperature" => "required",
                    "place_id" => "required|integer",
                    "place_type" => "required|in:Vehicle,Import_op_storage_md"

                ]);
            } catch (ValidationException $e) {
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
            $product = $place->product;
            if (!$product) {
                return response()->json(["msg" => "the product which you want is not exist"], 404);
            }
            $place->internal_temperature = $validated_values["temperature"];
            $place->save();
            $activ_violation = $place->violations()->where("parameter", "temperature")->where("job_id", "!=", null)->first();
            $job = null;
            if ($activ_violation) {
                $job = Job::find($activ_violation->job_id);
            }
            if (
                $validated_values["temperature"] < $product->highest_temperature
                && $validated_values["temperature"] > $product->lowest_temperature
            ) {


                if ($job) {
                    $job->delete($job->id);
                    $activ_violation->job_id = null;
                    $activ_violation->status = "handled";
                    $activ_violation->save();
                }
            } else {
                if (!$job) {

                    $violation = Violation::create(["parameter" => "temperature", "violable_id" => $place->id, "violable_type" => $model_place]);
                    $job = new TempViolation($violation);
                    $jobId = Queue::later(now()->addMinutes(120), $job);
                    $violation->job_id = $jobId;
                    $violation->save();
                }
            }
           
            


            DB::commit();
            return response()->json(["msg" => "done"], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(["error" => $e->getMessage()], 409);
        }
    }
}
