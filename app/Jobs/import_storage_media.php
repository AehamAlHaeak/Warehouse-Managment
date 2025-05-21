<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\AlgorithmsTrait;
use App\Models\Import_operation;
use App\Models\Import_op_storage_md;
use App\Models\Positions_on_sto_m;
use App\Models\Section;
use App\Models\Storage_media;

class import_storage_media implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use AlgorithmsTrait;
    protected $import_operation_id;
    protected $storage_media;
    public function __construct($import_operation_id, $storage_media)
    {
        $this->import_operation_id = $import_operation_id;
        $this->storage_media = $storage_media;
    }
    public function fetch_sections($related_storage_media)
    {
        //  $sections=$related_storage_media
    }

    public function ord_sections() {}

    public function distribute_storage_media() {}


    public function handle(): void
    {
        $created_storage_units = [];
        foreach ($this->storage_media as $storage_element) {
            $section = Section::find($storage_element["section_id"]);
            $section_empty_posetions = $section->posetions()->whereNull('storage_media_id')->get();
            $empty_count = $section_empty_posetions->count();
            $parent_storage_media = Storage_media::find($storage_element["storage_media_id"]);
            for ($count = 0; $count < min($storage_element["quantity"], $empty_count); $count++) {



                $storage_unit = Import_op_storage_md::create([
                    "storage_media_id" => $storage_element["storage_media_id"],
                    "import_operation_id" => $this->import_operation_id,

                ]);

                $section_empty_posetions[$count]->storage_media_id = $storage_unit->id;
                $section_empty_posetions[$count]->save();

                $storage_unit->num_floors = $parent_storage_media->num_floors;
                $storage_unit->num_classes = $parent_storage_media->num_classes;
                $storage_unit->num_positions_on_class = $parent_storage_media->num_positions_on_class;

                $created_storage_units[$storage_unit->id] = $storage_unit;
                $this->create_postions("App\\Models\\Positions_on_sto_m", $storage_unit, "imp_op_stor_id");
            }
        }
    }
}