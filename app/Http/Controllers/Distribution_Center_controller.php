<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Employe;
use App\Models\Section;
use App\Models\Transfer;
use App\Traits\LoadingTrait;
use Illuminate\Http\Request;
use app\Traits\TransferTrait;
use App\Models\Transfer_detail;
use App\Traits\AlgorithmsTrait;
use App\Models\DistributionCenter;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Import_op_container;
use App\Models\Imp_continer_product;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\reserved_details;
use App\Models\reject_details;
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
        if ($sections->isEmpty()) {
            return response()->json(["msg" => "there are no sections on this place"], 404);
        }
        return response()->json(["msg" => "sections on this place", "sections" => $sections], 202);
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
            $employe = $request->employe;
            if ($employe->specialization->name != "super_admin") {

                $authorized_in_place = $this->check_if_authorized_in_place($employe, $destination);
                if (!$authorized_in_place) {
                    return response()->json(["msg" => "Unauthorized - Invalid or missing employe token"], 401);
                }
            };
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
}
