<?php

namespace App\Traits;

use App\Models\Job;
use App\Models\Employe;
use App\Models\Violation;
use App\Jobs\TempViolation;
use Illuminate\Support\Str;
use App\Models\Specialization;
use App\Models\Import_op_storage_md;
use Illuminate\Support\Facades\Queue;
use Illuminate\Notifications\DatabaseNotification;
use App\Events\Send_Notification;
use App\Notifications\Violation_handled;
use App\Notifications\Violation_in_element;

trait ViolationsTrait
{
    use AlgorithmsTrait;
    public function reset_conditions_on_object($object)
    {
        $product = $object->product;

        $temp = ($product->highest_temperature + $product->lowest_temperature) / 2;
        $humidity = ($product->highest_humidity + $product->lowest_humidity) / 2;
        $light = ($product->highest_light + $product->lowest_light) / 2;
        $pressure = ($product->highest_pressure + $product->lowest_pressure) / 2;
        $ventilation = ($product->highest_ventilation + $product->lowest_ventilation) / 2;

        $object->internal_temperature = $temp;
        $object->internal_humidity = $humidity;
        $object->internal_light = $light;
        $object->internal_pressure = $pressure;
        $object->internal_ventilation = $ventilation;
        $object->save();
        return $object;
    }

    public function deal_with_variable_conditions($place, $parameter, $value)
    {

         
        $place->update(["internal_" . $parameter => $value]);

        $activ_violation = $place->violations()->where("parameter", $parameter)->where("job_id", "!=", null)->first();
       
        $job = null;

        if ($place instanceof Import_op_storage_md) {
                $section = $place->section()->first();
                $existable = $section->existable;
                $goal_spec_ids = Specialization::whereIn("name", ["warehouse_admin", "distribution_center_admin", "QA"])
                    ->pluck("id");
                $admins = $existable->employees()->whereIn("specialization_id", $goal_spec_ids)->get();
                $super_admin_spec_id = Specialization::where("name", "super_admin")->value("id");
                $super_admin = Employe::where("specialization_id", $super_admin_spec_id)->first();
                $admins->push($super_admin);
            } else {
                $driver = $place->driver;
                $transfer = $place->actual_transfer;
                $garage = $place->garage;
                $existable = $garage->existable;
                $super_admin_spec_id = Specialization::where("name", "super_admin")->value("id");
                $goal_spec_ids = Specialization::whereIn("name", ["warehouse_admin", "distribution_center_admin", "QA"])
                    ->pluck("id");
                $super_admin = Employe::where("specialization_id", $super_admin_spec_id)->first();
                $admins = $existable->employees()->whereIn("specialization_id", $goal_spec_ids)->get();
                $admins->push($super_admin);
                $admins->push($driver);
                $transfer = $place->actual_transfer;
                if ($transfer) {
                    $destination = $transfer->destinationable;
                    $class_dest = get_class($destination);
                    $class_exis = get_class($existable);
                    if (($class_dest != $class_exis && ($class_dest == "App\\Models\\Warehouse" || $class_dest == "App\\Models\\Distribution_center")) || $existable->id == $destination->id) {
                        $dest_admins = $destination->employees()->whereIn("specialization_id", $goal_spec_ids)->get();
                        $admins = $admins->merge($dest_admins);
                        $admins = $admins->unique();
                    }
                }
            }
        if ($activ_violation) {
            $job = Job::find($activ_violation->job_id);
        }
        if (
            $value < $place->product["highest_" . $parameter]
            && $value > $place->product["lowest_" . $parameter]
        ) {
            if ($job) {
                
                $job->delete($job->id);
                $activ_violation->job_id = null;
                $activ_violation->status = "handled";
                $activ_violation->save();
                 foreach ($admins as $employe) {
                $uuid = (string) Str::uuid();
                $notification = new Violation_handled($place, $activ_violation);

                $notify = DatabaseNotification::create([
                    'id' => $uuid,
                    'type' => get_class($notification),
                    'notifiable_type' => get_class($employe),
                    'notifiable_id' => $employe->id,
                    'data' => $notification->toArray($employe),
                    'read_at' => null,
                ]);
                $notification->id = $notify->id;
                event(new Send_Notification($employe, $notification));
            }
            }
        } else {
            
            foreach ($admins as $employe) {
                $uuid = (string) Str::uuid();
                $notification = new Violation_in_element($place, $parameter);

               $this->send_not($notification, $employe);
            }

            if (!$job) {
                $violation = Violation::create(["parameter" => $parameter, "violable_id" => $place->id, "violable_type" => get_class($place), "status" => "wait"]);
                $job = new TempViolation($violation->id);
                $jobId = Queue::later(now()->addMinutes(0), $job);
                $violation->job_id = $jobId;
                $violation->save();
            }
        }
        return "done";
    }
}
