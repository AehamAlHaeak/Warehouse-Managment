<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Garage;
use App\Models\Employe;
use App\Models\Section;
use App\Models\Transfer;
use App\Traits\LoadingTrait;
use Illuminate\Http\Request;
use app\Traits\TransferTrait;
use App\Models\reject_details;
use App\Models\Transfer_detail;
use App\Traits\AlgorithmsTrait;
use App\Models\reserved_details;
use App\Models\DistributionCenter;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Import_op_container;
use App\Models\Imp_continer_product;
use App\Models\Import_op_storage_md;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\HttpCache\ResponseCacheStrategy;

class Distribution_Center_controller extends Controller
{
    use AlgorithmsTrait;
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
            echo $reserved_load_count . " " . $remine_load;
            if ($reserved_load_count >= $remine_load) {

                $imp_op_products = $container->imp_op_product;
                $parent_product = $imp_op_products[0]->parent_product;
                $sections_of_product_in_place = $destination->sections()->where("product_id", $parent_product->id)->get();

                foreach ($reserved_load as $res_load) {

                    $another_content_in_same_continer = Imp_continer_product::where("imp_op_cont_id", $container->id)->where("id", "!=", $content->id)->get();

                    // $res_load=$this->move_reserved_from_to($another_content_in_same_continer, $res_load);
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
                                $containers = $storage_element->impo_container()
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
    public function move_containers(Request $request)
    {
        try {
            try {
                $validated_values = $request->validate([
                    "containers_ids" => "required|array|min:1",

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
                        return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
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

                $first_empty_posetion_on_sto_m = $storage_element->posetions()->whereNull("imp_op_contin_id")->first();
                $first_empty_posetion_on_sto_m->update(["imp_op_contin_id" => $container->id]);
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
            $sections = $place->sections;
            foreach ($sections as $section) {
                $product = $section->product;
                $products[$product->id] = $product;
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
    public function show_incoming_transfers(Request $request, $place_type, $place_id) {
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
            $live=collect();
            $archiv=collect();
            $wait=collect();

            $incoming_transfers = $place->resived_transfers()->with("sourceable")->get();
              foreach($incoming_transfers as $incoming_transfer) {
                if(!$incoming_transfer->contents()) {
                  $incoming_transfer->status="return";
                  
                }
                else {
                  $incoming_transfer->status="contained";
                }
               if($incoming_transfer->date_of_resiving!=null && $incoming_transfer->date_of_finishing==null) {
                $live->push($incoming_transfer);
               }
               elseif($incoming_transfer->date_of_resiving!=null && $incoming_transfer->date_of_finishing!=null){
                   $archiv->push($incoming_transfer); 
               }
               elseif($incoming_transfer->date_of_resiving==null && $incoming_transfer->date_of_finishing==null) {
                $wait->push($incoming_transfer);
               }
              }
               

            if ($incoming_transfers->isEmpty()) {
                return response()->json(["msg" => "there are no incoming transfers on this place"], 404);
            }
            return response()->json(["msg" => "incoming transfers on this place","live"=>$live,"archiv"=>$archiv,"wait"=>$wait], 202);

    }catch (Exception $e) {
        return response()->json(["msg" => $e->getMessage()], 500);
    }

}

}