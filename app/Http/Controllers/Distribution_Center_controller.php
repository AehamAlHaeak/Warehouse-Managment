<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Garage;
use App\Models\Employe;
use App\Models\Product;
use App\Models\Section;
use App\Models\Vehicle;
use App\Models\Transfer;
use App\Traits\LoadingTrait;
use Illuminate\Http\Request;
use app\Traits\TransferTrait;
use App\Models\reject_details;
use App\Models\Transfer_detail;
use App\Traits\AlgorithmsTrait;
use App\Traits\ViolationsTrait;
use Illuminate\Validation\Rule;
use App\Models\reserved_details;
use App\Traits\TransferTraitAeh;
use App\Models\container_movments;
use App\Models\DistributionCenter;
use App\Models\Positions_on_sto_m;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Import_op_container;
use Illuminate\Support\Facades\Log;
use App\Models\Imp_continer_product;
use App\Models\Import_op_storage_md;
use App\Models\Posetions_on_section;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\HttpCache\ResponseCacheStrategy;

class Distribution_Center_controller extends Controller
{
    use AlgorithmsTrait;
    use TransferTraitAeh;
    use ViolationsTrait;
    public function show_my_work_place(Request $request)
    {
        try {

            $employe = $request->employe;

            if ($employe->specialization->name == "super_admin") {
                return response()->json(["msg" => "you dont have specific work place you are the super admin"], 401);
            }

            $work_place = $employe->workable;
            $work_place->place_type = str_replace("App\\Models\\", "", get_class($work_place));


            $products = [];
            $sections = $work_place->sections()->distinct('product_id')
                ->get();
            unset($work_place["sections"]);
            foreach ($sections as $section) {


                $product = $section->product;
                $products[$product->id] = $this->inventry_product_in_place($product, $work_place);
            }
            if (empty($products)) {
                return response()->json(["msg" => "there are no products on this place"], 404);
            }
            foreach ($products as $product) {
                $product = $this->inventry_product_in_place($product, $work_place);
            }
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
        return response()->json(["msg" => "here your work place ", "work_place" => $work_place, 'products' => $products], 202);
    }


    public function show_employees_on_place(Request $request, $place_type, $place_id)
    {
        $model = "App\\Models\\" . $place_type;

        $place = $model::find($place_id);
        $employe = $request->employe;
        if ($employe->specialization->name != "super_admin") {

            $authorized_in_place = $this->check_if_authorized_in_place($employe, $place);
            if (!$authorized_in_place) {
                return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
            }
        }


        $employees = $place->employees;
        if ($employees->isEmpty()) {
            return response()->json(["msg" => "there are no employees on this place"], 404);
        }
        return response()->json(["msg" => "employees on this place", "employees" => $employees], 202);
    }





    public function show_sections_on_place(Request $request, $place_type, $place_id)
    {
        try {
            $model = "App\\Models\\" . $place_type;

            $place = $model::find($place_id);

            if (!$place) {
                return response()->json(["msg" => "the place which you want is not exist"], 404);
            }
            $employe = $request->employe;
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $place);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
                }
            }

            $sections = $place->sections()->with("product")->get();
            foreach ($sections as $section) {
                $section = $this->calculate_areas($section);
            }
            if ($sections->isEmpty()) {
                return response()->json(["msg" => "there are no sections on this place"], 404);
            }
            return response()->json(["msg" => "sections on this place", "sections" => $sections], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 404);
        }
    }

    public function show_storage_elements_on_section(Request $request, $section_id)
    {
        $section = Section::find($section_id);
        if (!$section) {
            return response()->json(["msg" => "the section which you want is not exist"], 404);
        }
        $place = $section->existable;
        $employe = $request->employe;
        if ($employe->specialization->name != "super_admin") {

            $authorized_in_place = $this->check_if_authorized_in_place($employe, $place);
            if (!$authorized_in_place) {
                return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
            }
        }


        $storage_elements = $section->storage_elements;
        if ($storage_elements->isEmpty()) {
            return response()->json(["msg" => "there are no storage elements on this section"], 404);
        }
        unset($section["storage_elements"]);
        $parent_storage_media = $storage_elements[0]->parent_storage_media;
        unset($storage_elements[0]["parent_storage_media"]);

        return response()->json(["msg" => "storage elements on this section", "parent_storage_media" => $parent_storage_media, "storage_elements" => $storage_elements], 202);
    }

    public function show_empty_posetions_on_storage_element(Request $request, $storage_element_id)
    {
        try {
            $storage_element = Import_op_storage_md::find($storage_element_id);
            if (!$storage_element) {
                return response()->json(["msg" => "the storage element which you want is not exist"], 404);
            }
            $section = $storage_element->section()->first();
            if (!$section) {
                return response()->json(["msg" => "the section which you want is not exist"], 404);
            }
            $place = $section->existable;

            $employe = $request->employe;
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $place);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
                }
            }
            $posetions = $storage_element->posetions()->whereNull("imp_op_contin_id")->get();

            if ($posetions->isEmpty()) {
                return response()->json(["msg" => "there are no empty posetions on this storage element"], 404);
            }
            return response()->json(["msg" => "empty posetions on this storage element", "posetions" => $posetions], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function show_continers_on_storage_element(Request $request, $storage_element_id)
    {
        try {

            $storage_element = Import_op_storage_md::find($storage_element_id);
            if (!$storage_element) {
                return response()->json(["msg" => "the storage element which you want is not exist"], 404);
            }
            $section = $storage_element->section()->first();
            if (!$section) {
                return response()->json(["msg" => "the section which you want is not exist"], 404);
            }
            $place = $section->existable;

            $employe = $request->employe;
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $place);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
                }
            }

            $continers = $storage_element->continers->makeHidden(['pivot']);

            if ($continers->isEmpty()) {
                return response()->json(["msg" => "there are no continers on this storage element"], 404);
            }

            return response()->json(["msg" => "continers on this storage element", "continers" => $continers], 202);
        } catch (Exception $e) {

            return response()->json(["msg" => $e->getMessage()], 404);
        }
    }


    public function show_actual_loads(Request $request)
    {

        try {
            $QA = $request->employe;
            $place = $QA->workable;


            $activ_transfers = $place->resived_transfers()->where("date_of_resiving", "!=", null)->where("date_of_finishing", null)->get();
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
    public function show_load_details(Request $request, $load_id)
    {
        $load = Transfer_detail::find($load_id);
        $transfer = $load->transfer;
        $destination = $transfer->destinationable;
        $employe = $request->employe;
        if ($employe->specialization->name != "super_admin") {

            $authorized_in_place = $this->check_if_authorized_in_place($employe, $destination);
            if (!$authorized_in_place) {
                return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
            }
        }



        $continers = $load->continers;
        if ($continers->isEmpty()) {
            return response()->json(['msg' => 'No containers in this load'], 404);
        }
        return response()->json(["msg" => "here the load details", "continers" => $load], 202);
    }

    public function show_container_details(Request $request, $load_id)
    {
        try {
            $continer = Import_op_container::find($load_id);
            if (!$continer) {
                return response()->json(['msg' => 'Container not found'], 404);
            }
            $latest_trans = $continer->logs->last();
            $transfer = $latest_trans->transfer;

            $destination = $transfer->destinationable;
            $employe = $request->employe;
            $employe = $request->employe;
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $destination);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
                }
            }

            $contents = [];

            $i = 0;
            $contined_load = Imp_continer_product::where("imp_op_cont_id", $continer->id)->get();

            foreach ($contined_load as $load) {
                $imp_op_product = $load->impo_op_product;
                $logs = $this->calculate_load_logs($load);

                $imp_op_product->selled_load = $logs["selled_load"];
                $imp_op_product->reserved_load = $logs["reserved_load"];
                $imp_op_product->rejected_load = $logs["rejected_load"];
                $imp_op_product->remine_load = $logs["remine_load"];

                $parent_product = $imp_op_product->parent_product;
                unset($imp_op_product["pivot"]);
                unset($imp_op_product["parent_product"]);
                $parent_product->setting_id = $load->id;
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

    public function reject_content_from_continer(Request $request)
    {
        try {
            try {
                $validated_values = $request->validate([
                    "content_id" => "required",
                    "why" => "required|string",
                    "quantity" => "required|numeric|min:1",
                ]);
            } catch (ValidationException $e) {

                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            $content = Imp_continer_product::find($request->content_id);
            if (!$content) {
                return response()->json(["msg" => "contnt not found"], 404);
            }

            $container = $content->container;
            $latest_trans = $container->logs->last();

            $transfer = $latest_trans->transfer;

            $destination = $transfer->destinationable;
            $employe = $request->employe;

            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $destination);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
                }
            }
            $logs = $this->calculate_load_logs($content);
            $selled_load = $logs["selled_load"];
            $reserved_load = $content->reserved_load;
            $reserved_load_count = $logs["reserved_load"];
            $rejected_load = $logs["rejected_load"];


            $remine_load = $content->load - $selled_load - $rejected_load;

            if ($validated_values["quantity"] > $remine_load) {
                return response()->json(["msg" => "rejected lod more than remine load"], 400);
            }

            if ($reserved_load_count >= $remine_load) {

                $imp_op_products = $container->imp_op_product;
                $parent_product = $imp_op_products[0]->parent_product;
                $sections_of_product_in_place = $destination->sections()->where("product_id", $parent_product->id)->get();

                foreach ($reserved_load as $res_load) {

                    $another_content_in_same_continer = Imp_continer_product::where("imp_op_cont_id", $container->id)->where("id", "!=", $content->id)->get();


                    foreach ($another_content_in_same_continer as $another_load) {
                        $logs = $this->calculate_load_logs($another_load);

                        $moved_reserved = min($logs["remine_load"], $res_load->reserved_load);
                        reserved_details::create([
                            "transfer_details_id" => $res_load->transfer_details_id,
                            "reserved_load" => $moved_reserved,
                            "imp_cont_prod_id" => $another_load->id
                        ]);

                        $res_load->reserved_load = $res_load->reserved_load - $moved_reserved;
                        $res_load->save();
                        if ($res_load->reserved_load == 0) {
                            $res_load->delete($res_load->id);
                        }
                    }
                    if ($res_load->reserved_load > 0) {
                        foreach ($sections_of_product_in_place as $section) {
                            $storage_elements = $section->storage_elements;
                            unset($section["storage_elements"]);
                            foreach ($storage_elements as $storage_element) {
                                $containers = $storage_element->impo_container()->wher("status", "accepted")
                                    ->with('imp_op_product')
                                    ->get()
                                    ->sortBy(function ($container) {
                                        return $container->imp_op_product
                                            ->pluck('expiration')
                                            ->filter()
                                            ->min();
                                    })
                                    ->values();

                                foreach ($containers as $continer) {
                                    $contents_in_continer = Imp_continer_product::where("imp_op_cont_id", $continer->id)->get();


                                    //  $res_load=$this->move_reserved_from_to($contents_in_continer, $res_load);
                                    foreach ($contents_in_continer as $another_load) {




                                        $logs = $this->calculate_load_logs($another_load);

                                        $moved_reserved = min($logs["remine_load"], $res_load->reserved_load);

                                        $res = reserved_details::create([
                                            "transfer_details_id" => $res_load->transfer_details_id,
                                            "reserved_load" => $moved_reserved,
                                            "imp_cont_prod_id" => $another_load->id
                                        ]);

                                        $res_load->reserved_load = $res_load->reserved_load - $moved_reserved;

                                        $res_load->save();
                                        if ($res_load->reserved_load == 0) {
                                            break 3;
                                            $res_load->delete($res_load->id);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($res_load->reserved_load == 0) {

                        $res_load->delete($res_load->id);
                        continue;
                    }
                }
            }

            $rejected_load = reject_details::create([
                "employe_id" => $employe->id,
                "rejected_load" => $validated_values["quantity"],
                "imp_cont_prod_id" => $content->id,
                "why" => $validated_values["why"]
            ]);

            $contined_load = Imp_continer_product::where("imp_op_cont_id", $container->id)->get();
            $total_load = 0;
            $total_salled = 0;
            $total_reserved = 0;

            $total_remine = 0;
            foreach ($contined_load as $load) {
                $total_load += $load->load;
                $logs = $this->calculate_load_logs($load);
                $total_salled += $logs["selled_load"];

                $total_reserved += $logs["reserved_load"];

                $total_remine += $logs["remine_load"];

                $load->save();
            }

            if ($total_remine == 0) {
                if ($total_salled != 0) {
                    $container->update([
                        "status" => "sold"
                    ]);
                } else {
                    if ($total_reserved == 0) {
                        $container->update([
                            "status" => "rejected"
                        ]);
                    }
                }
            }
            return response()->json(["msg" => "here the details", "product" => $rejected_load, "container" => $container], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function accept_continer(Request $request, $container_id)
    {
        $container = Import_op_container::find($container_id);
        if (!$container) {
            return response()->json(['msg' => 'Container not found'], 404);
        }
        $latest_trans = $container->logs->last();
        unset($container["logs"]);
        $transfer = $latest_trans->transfer;

        $destination = $transfer->destinationable;
        $employe = $request->employe;
        $employe = $request->employe;
        if ($employe->specialization->name != "super_admin") {

            $authorized_in_place = $this->check_if_authorized_in_place($employe, $destination);
            if (!$authorized_in_place) {
                return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
            }
        }
        if ($container->status == "rejected") {

            return response()->json(["msg" => "this container is rejected and can't be accepted"], 404);
        }
        $container->status = "accepted";
        $container->save();
        return response()->json(["msg" => "here the details", "container" => $container], 202);
    }
    public function move_to_position(Request $request)
    {
        try {
            $validated = $request->validate([
                'container_id' => 'required|integer|exists:import_op_containers,id',
                'position_id' => 'required|integer|exists:Positions_on_sto_m,id',
                'why' => 'required|string'
            ]);

            $container = Import_op_container::find($validated['container_id']);
            $position = Positions_on_sto_m::find($validated['position_id']);


            if ($position->imp_op_contin_id !== null) {
                return response()->json(['msg' => 'This position is already occupied'], 400);
            }


            if ($container->status !== 'accepted') {
                return response()->json(['msg' => 'Only accepted containers can be moved'], 400);
            }


            $latest_trans = $container->logs->last();
            unset($container["logs"]);
            $transfer = $latest_trans->transfer;

            $place = $transfer->destinationable;
            $employee = $request->employe;
            if ($employee->specialization->name !== "super_admin") {
                $authorized = $this->check_if_authorized_in_place($employee, $place);
                if (!$authorized) {
                    return response()->json(["msg" => "Unauthorized or dont have the container yet"], 401);
                }
            }
            $storage_element = $position->storage_element;
            $place = $storage_element->section->first()->existable;

            if ($employee->specialization->name !== "super_admin") {
                $authorized = $this->check_if_authorized_in_place($employee, $place);
                if (!$authorized) {
                    return response()->json(["msg" => "Unauthorized in posetion place"], 401);
                }
            }


            $old_position = $container->posetion_on_stom;
            if ($old_position) {
                container_movments::create([
                    "imp_op_cont_id" => $container->id,
                    "prev_position_id" => $old_position->id,
                    "moved_why" => $validated["why"]
                ]);


                $old_position->update(["imp_op_contin_id" => null]);
            }


            $position->update(["imp_op_contin_id" => $container->id]);

            return response()->json(['msg' => 'Container moved successfully'], 202);
        } catch (ValidationException $e) {
            return response()->json([
                'msg' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['msg' => $e->getMessage()], 500);
        }
    }




    public function move_containers(Request $request)
    {
        try {
            try {
                $validated_values = $request->validate([
                    "containers_ids" => "required|array|min:1",
                    "why" => "required|string",
                    "destination_storage_media_id" => "required|integer"

                ]);
            } catch (ValidationException $e) {

                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            $storage_element = Import_op_storage_md::find($validated_values["destination_storage_media_id"]);
            if (!$storage_element) {
                return response()->json(["msg" => "storage_media not found"], 404);
            }
            $employe = $request->employe;
            $section = $storage_element->section->first();
            $place = $section->existable;
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $place);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
                }
            }
            $containers = Import_op_container::whereIn("id", $validated_values["containers_ids"])->get();


            $avilable_area_on_sto_m = $storage_element->posetions->whereNull("imp_op_contin_id")->count();
            if ($avilable_area_on_sto_m <= $containers->count()) {
                return response()->json(["msg" => "there is no avilable area on storage media for all continers", "avilable_area" => $avilable_area_on_sto_m], 400);
            }
            foreach ($containers as $container) {
                if ($container->status != "accepted") {
                    return response()->json(["msg" => "you cannot move this continer because its status:", "continer" => $container], 400);
                }
                $latest_trans = $container->logs->last();
                unset($container["logs"]);
                $transfer = $latest_trans->transfer;

                $destination = $transfer->destinationable;
                if ($employe->specialization->name != "super_admin") {

                    $authorized_in_place = $this->check_if_authorized_in_place($employe, $destination);
                    if (!$authorized_in_place) {
                        return response()->json(["msg" => "Unauthorized - Invalid or missing employe token or you dont have the contianer {$container->id}"], 401);
                    }
                }
                if ($latest_trans->status != "in_QA" && !$latest_trans->status != "resived") {
                    return response()->json(["msg" => "you dont have this continer yet!", "continer" => $container], 400);
                }
                $parent_continer = $container->parent_continer;
                $parent_storage_media = $parent_continer->storage_media;
                if ($storage_element->storage_media_id != $parent_storage_media->id) {
                    return response()->json(["msg" => "the storage_media not smoth with continer!", "continer" => $container, "storage_media" => $parent_storage_media], 400);
                }

                $last_empty_posetion_on_sto_m = $storage_element->posetions()->whereNull("imp_op_contin_id")->orderByDesc('id')->first();
                $posetion = $container->posetion_on_stom;
                if ($posetion) {
                    container_movments::create([
                        "imp_op_cont_id" => $container->id,
                        "prev_position_id" => $posetion->id,

                        "moved_why" => $validated_values["why"]
                    ]);
                    $posetion->update(["imp_op_contin_id" => null]);
                }
                $last_empty_posetion_on_sto_m->update(["imp_op_contin_id" => $container->id]);
            }
            return response()->json(["msg" => "containers moved succesfolly!"], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function show_garages_on_place(Request $request, $place_type, $place_id)
    {
        try {
            $model = "App\\Models\\" . $place_type;
            $place = $model::find($place_id);
            if (!$place) {
                return response()->json(["msg" => "the place which you want is not exist"], 404);
            }
            $employe = $request->employe;
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $place);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
                }
            }
            $garages = $place->garages;
            if ($garages->isEmpty()) {
                return response()->json(["msg" => "there are no garages on this place"], 404);
            }
            return response()->json(["msg" => "garages on this place", "garages" => $garages], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function show_vehicles_of_garage(Request $request, $garage_id)
    {
        try {
            $garage = Garage::find($garage_id);
            if (!$garage) {
                return response()->json(["msg" => "garage not found"], 404);
            }
            $place = $garage->existable;
            $employe = $request->employe;
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $place);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
                }
            }
            $vehicles = $garage->vehicles;
            if ($vehicles->isEmpty()) {
                return response()->json(["msg" => "there are no vehicles on this garage"], 404);
            }
            return response()->json(["msg" => "vehicles on this garage", "vehicles" => $vehicles], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function show_products_of_place(Request $request, $place_type, $place_id)
    {
        try {
            $model = "App\\Models\\" . $place_type;
            $place = $model::find($place_id);
            if (!$place) {
                return response()->json(["msg" => "the place which you want is not exist"], 404);
            }

            $employe = $request->employe;
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $place);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
                }
            }
            $products = [];
            $sections = $place->sections()->distinct('product_id')
                ->get();
            foreach ($sections as $section) {

                $product = $section->product;
                $products[$product->id] = $this->inventry_product_in_place($product, $place);
            }
            if (empty($products)) {
                return response()->json(["msg" => "there are no products on this place"], 404);
            }
            foreach ($products as $product) {
                $product = $this->inventry_product_in_place($product, $place);
            }
            return response()->json(["msg" => "products on this place", "products" => $products], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function show_incoming_transfers(Request $request, $place_type, $place_id)
    {
        try {
            $model = "App\\Models\\" . $place_type;
            $place = $model::find($place_id);
            if (!$place) {
                return response()->json(["msg" => "the place which you want is not exist"], 404);
            }
            $employe = $request->employe;
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $place);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
                }
            }
            $live = collect();
            $archiv = collect();
            $wait = collect();

            $incoming_transfers = $place->resived_transfers()->with("sourceable")->get();
            foreach ($incoming_transfers as $incoming_transfer) {
                if (!$incoming_transfer->contents()) {
                    $incoming_transfer->status = "return";
                } else {
                    $incoming_transfer->status = "contained";
                }
                if ($incoming_transfer->date_of_resiving != null && $incoming_transfer->date_of_finishing == null) {
                    $live->push($incoming_transfer);
                } elseif ($incoming_transfer->date_of_resiving != null && $incoming_transfer->date_of_finishing != null) {
                    $archiv->push($incoming_transfer);
                } elseif ($incoming_transfer->date_of_resiving == null && $incoming_transfer->date_of_finishing == null) {
                    $wait->push($incoming_transfer);
                }
            }


            if ($incoming_transfers->isEmpty()) {
                return response()->json(["msg" => "there are no incoming transfers on this place"], 404);
            }
            return response()->json(["msg" => "incoming transfers on this place", "live" => $live, "archiv" => $archiv, "wait" => $wait], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function ask_products_from_up(Request $request)
    {
        try {
            try {
                $validated_values = $request->validate([
                    "products" => "required|array|min:1",
                    "products.*.product_id" => "required|integer|exists:products,id",
                    "products.*quantity" => "required|integer",
                    "destination_type" => "required|in:Warehouse,DistributionCenter",
                    "destination_id" => "required",


                ]);
            } catch (ValidationException $e) {

                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            $employe = $request->employe;
            $model = "App\\Models\\" . $request->destination_type;
            $destination = $model::find($request->destination_id);
            if (!$destination) {
                return response()->json(["msg" => "the destination which you want is not exist"], 404);
            }
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $destination);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
                }
            }
            foreach ($validated_values["products"] as $block) {
                $inventory_of_incoming = 0;
                $product = Product::find($block["product_id"]);
                $product_continer = $product->container;
                $transfers = $destination->resived_transfers()->whereNull("date_of_finishing")->get();
                foreach ($transfers as $transfer) {

                    foreach ($transfer->transfer_details as $detail) {
                        $containers_count = $detail->continers()->where("container_type_id", $product_continer->id)->where("status", "accepted")->whereDoesntHave("posetion_on_stom")->count();

                        $inventory_of_incoming += $containers_count * $product_continer->capacity;
                    }
                }
                $destination = $this->calcute_areas_on_place_for_a_specific_product($destination, $product->id);

                if ($block["quantity"] > $destination->avilable_area_product) {
                    return response()->json(["msg" => "the destination dont have enough space for this product"], 404);
                }
                $may_be_a_load_in_des = $inventory_of_incoming + $block["quantity"];
                if ($may_be_a_load_in_des > $destination->avilable_area_product) {
                    return response()->json([
                        "msg" => "The total of containers already incoming and the new quantity exceeds the available space in your work place."
                    ], 409);
                }
            }
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function pass_load(Request $request)
    {
        try {
            try {
                $validated_values = $request->validate([
                    "load_id" => "required|integer"
                ]);
            } catch (ValidationException $e) {

                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }



            $load = Transfer_detail::find($validated_values["load_id"]);

            $transfer = $load->transfer;
            $destination = $transfer->destinationable;
            $employe = $request->employe;

            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $destination);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
                }
            }
            if ($load->status != "in_QA") {
                return response()->json(["msg" => "the load is not in QA"], 409);
            }
            DB::beginTransaction();
            try {

                $continers = $this->unload($load, $destination);


                $load->update(["status" => "received"]);
                DB::commit();
                return response()->json(["msg" => "load passed successfully", "destination" => $destination, "continers" => $continers], 202);
            } catch (Exception $e) {
                DB::rollBack();
                return response()->json(["msg" => $e->getMessage()], 500);
            }
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function set_driver_for_vehicle(Request $request)
    {
        try {
            try {
                $validated_values = $request->validate([
                    "vehicle_id" => "required|integer",
                    "driver_id" => "required|integer"
                ]);
            } catch (ValidationException $e) {

                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            $driver = Employe::find($validated_values["driver_id"]);
            if (!$driver) {
                return response()->json(["msg" => "the driver which you want is not exist"], 404);
            }

            $vehicle = Vehicle::find($validated_values["vehicle_id"]);
            if (!$vehicle) {
                return response()->json(["msg" => "the vehicle which you want is not exist"], 404);
            }
            $employe = $request->employe;
            $work_place_of_driver = $driver->workable;
            $garage = $vehicle->garage;
            $work_place_of_vehicle = $garage->existable;
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $work_place_of_driver);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe or dont have this employe"], 401);
                }
                $authorized_in_place = $this->check_if_authorized_in_place($employe, $work_place_of_vehicle);
            }
            $spec_of_driver = $driver->specialization;
            if ($spec_of_driver->name != "driver") {
                return response()->json(["msg" => "the employe you want is not a driver"], 404);
            }

            if (get_class($work_place_of_driver) !== get_class($work_place_of_vehicle)) {
                return response()->json(["msg" => "the driver and the vehicle are not in the same place"], 404);
            }
            if ($work_place_of_driver->id != $work_place_of_vehicle->id) {
                return response()->json(["msg" => "the driver and the vehicle are not in the same place"], 404);
            }
            if ($vehicle->transfer_id != null) {
                return response()->json(["msg" => "the vehicle is in a transfer"], 404);
            }

            if ($vehicle->driver_id != null) {
                //send notefication he will cansell the truck
            }
            $driver_vehicle = $driver->vehicle;
            if ($driver_vehicle) {
                return response()->json(["msg" => "the driver already has a vehicle"], 404);
            }

            $vehicle->update(["driver_id" => $driver->id]);
            return response()->json(["msg" => "driver set successfully"], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function remove_driver(Request $request, $vehicle_id)
    {
        try {
            $vehicle = Vehicle::find($vehicle_id);
            if (!$vehicle) {
                return response()->json(["msg" => "the vehicle which you want is not exist"], 404);
            }
            $work_place_of_vehicle = $vehicle->garage->existable;
            $employe = $request->employe;
            if ($employe->specialization->name != "super_admin") {
                $authorized_in_place = $this->check_if_authorized_in_place($employe, $work_place_of_vehicle);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe or dont have this employe"], 401);
                }
            }
            $driver = $vehicle->driver;
            if ($driver) {
                //send notefication he will cansel the truck
            }
            if ($vehicle->driver_id == null) {
                return response()->json(["msg" => "the vehicle has no driver"], 404);
            }
            $vehicle->update(["driver_id" => null]);
            return response()->json(["msg" => "driver removed successfully"], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function reset_conditions_in_place(Request $request)
    {
        DB::beginTransaction();
        try {
            try {
                $valedated_values = $request->validate([
                    "place_id" => "required|integer",
                    "place_type" => "required|in:Vehicle,Import_op_storage_md",
                ]);
            } catch (ValidationException $e) {

                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            $employe = $request->employe;
            $model_place = "App\\Models\\" . $valedated_values["place_type"];
            $place = $model_place::find($valedated_values["place_id"]);
            if (!$place) {
                return response()->json(["msg" => "the place which you want is not exist"], 404);
            }
            if (get_class($place) == "App\Models\Vehicle") {

                if ($place->transfer_id != null) {
                    return response()->json(["msg" => "the vehicle is in a transfer"], 400);
                }
                $garage = $place->garage;
                $existable = $garage->existable;
            } elseif (get_class($place) == "App\Models\Import_op_storage_md") {
                $section = $place->section->first();
                $existable = $section->existable;
            }
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $existable);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
                }
            }
            $parameters = ["temperature", "humidity", "light", "pressure", "ventilation"];
            $product = $place->product;
            $place->readiness = 1;
            foreach ($parameters as $parameter) {

                $value = ($product["highest_" . $parameter] + $product["lowest_" . $parameter]) / 2;
                $this->deal_with_variable_conditions($place, $parameter, $value);
            }


            DB::commit();
            return response()->json(["msg" => "conditions reset successfully"], 202);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function reject_continer(Request $request)
    {
        DB::beginTransaction();
        try {
            try {
                $validated_values = $request->validate([
                    "continer_id" => "required",
                    "why" => "required|string",

                ]);
            } catch (ValidationException $e) {

                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }



            $continer = Import_op_container::find($validated_values["continer_id"]);

            if (!$continer) {
                return response()->json(["msg" => "continer_not found"], 404);
            }

            $employe = $request->employe;
            $latest_trans = $continer->logs->last();
            unset($continer["logs"]);
            $transfer = $latest_trans->transfer;

            $place = $transfer->destinationable;
            $employee = $request->employe;
            if ($employee->specialization->name !== "super_admin") {
                $authorized = $this->check_if_authorized_in_place($employe, $place);
                if (!$authorized) {
                    return response()->json(["msg" => "Unauthorized or dont have the container yet"], 401);
                }
            }
            if ($continer->status == "rejected") {
                return response()->json(["msg" => "continer rejected already"], 400);
            } elseif ($continer->status == 'sold') {
                return response()->json(["msg" => "continer is sold"], 400);
            }

            if ($latest_trans->status == "under_work" || $latest_trans->status == "wait" || $latest_trans->status == "cut") {
                return response()->json(["msg" => "you dont have the continer yet"]);
            }
            $loads_in_continer = $continer->loads;
            $continer->status = "rejected";
            $continer->save();
            $avilable_sections = $place->sections;
            $new_ids = [];
            $has_reserving = $continer->loads()->whereDoesntHave('reserved_load')->exists();
            if (!$has_reserving) {
                foreach ($avilable_sections as $section) {

                    $storage_elements = $section->storage_elements()->where("readiness", ">", 0.8)->get();

                    $new_ids = $this->move_reserved_from_container($storage_elements, $continer);

                    if (!empty($new_ids)) {
                        break;
                    }
                }
                if (empty($new_ids)) {

                    throw new \Exception("The destination does not have enough containers for all reservations.");
                }
            }


            foreach ($loads_in_continer as $load) {
                $remine_load = $this->calculate_load_logs($load)["remine_load"];

                if ($remine_load > 0) {
                    reject_details::create([
                        "employe_id" => $employe->id,
                        "rejected_load" => $remine_load,
                        "imp_cont_prod_id" => $load->id,
                        "why" => $validated_values["why"]
                    ]);
                }
            }


            DB::commit();
            return response()->json(["msg" => "rejected succesfuly"]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }


    public function search()
    {
        try {
        /*
        "Containers_type",
        "Continer_transfer",
        "DistributionCenter",
        "Employe",
        "Favorite",
        "Garage",
        "Imp_continer_product",
        "Import_op_container",
        "Import_op_storage_md",
        "Import_operation",
        "Import_operation_product",
        "Invoice",
        "Job",
        "MovableProduct",
        "Posetions_on_section",
        "Positions_on_sto_m",
        "Product",
        "Request_detail",
        "Requests",
        "Section",
        "Sell_detail",
        "Specialization",
        "Storage_media",
        "Supplier",
        "Supplier_Details",
        "Transfer",
        "Transfer_detail",
        "User",
        "Vehicle",
        "Violation",
        "Warehouse",
        "container_movments",
        "reject_details",
        "reserved_details",
        "type" all filters
         */
            $modelNames = collect(File::files(app_path('Models')))
                ->map(fn($file) => pathinfo($file, PATHINFO_FILENAME))
                ->all();

            try {
                $validated_values = request()->validate([
                    "filter" => ["required", Rule::in($modelNames)],
                    "value" => ["required", 'regex:/^[a-zA-Z0-9 ]+$/']
                ]);
            } catch (ValidationException $e) {

                return response()->json([
                    'msg' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            $model = "App\\Models\\" . $validated_values["filter"];
            $value = $validated_values["value"];
            $columns = Schema::getColumnListing((new $model)->getTable());

            $results = $model::where(function ($query) use ($columns,  $value) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'LIKE', "%$value%");
                }
            })->get();


            return response()->json(["msg" => "search results", "filter" => $validated_values["filter"], "results" => $results], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function show_continer_movments(Request $request,$cont_id)
    {
        try {
            $continer = Import_op_container::find($cont_id);
            if (!$continer) {
                return response()->json(["msg" => "continer not found"], 404);
            }
             $latest_trans = $continer->logs->last();
            $transfer = $latest_trans->transfer;

            $destination = $transfer->destinationable;
            $source=$transfer->sourceable;
            $employe = $request->employe;
            $employe = $request->employe;
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $destination);
                $authorized_in_place2=$this->check_if_authorized_in_place($employe, $source);
                if (!$authorized_in_place && !$authorized_in_place2) {
                    return response()->json(["msg" => "you are not last destination or source!"], 401);
                }
            }
            $actual_posetion = $continer->posetion_on_stom()->select(["imp_op_stor_id", "floor", "class", "positions_on_class"])->first();
            
            $movments = $continer->movments()->select(["id","imp_op_cont_id","prev_position_id","moved_why"])->orderby("created_at", "desc")->get();

           
            foreach ($movments as $movment) {
                $posetion = $movment->posetion_on_sto_m()->select(["imp_op_stor_id", "floor", "class", "positions_on_class"])->first();

           
                $storage_element = $posetion->storage_element;
                
                $posetion_of_storage_element = $storage_element->posetion_on_section()->select(["id","section_id","floor","class","positions_on_class",])->first();
         
                $section = $posetion_of_storage_element->section()->select(["id","name","existable_type","existable_id"])->first();
              
                $place = $section->existable;
                 unset($section["existable"]);
                 unset($place->created_at,$place->updated_at);
                $movment->place_type = str_replace("App\\Models\\", "", get_class($place));
                $movment->place = $place;
                $movment->section = $section;
                $movment->storage_element = $storage_element;
                $movment->posetion_of_storage_element = $posetion_of_storage_element;
                $movment->posetion = $posetion;
            }

            return response()->json(["msg" => "success", "continer" => $continer, "actual_posetion" => $actual_posetion, "movments" => $movments], 202);
        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
}
