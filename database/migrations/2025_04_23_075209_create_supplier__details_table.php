<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplier__details', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("supplier_id");
            $table->foreign("supplier_id")->references("id")->on("suppliers");
            $table->nullableMorphs("suppliesable");
            //may be vehicles or storage_media or products where it null then it supply vehicles 
            $table->double("max_delivery_time_by_days");
            $table->unique(["supplier_id","suppliesable_type","suppliesable_id"],"supply_info");
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier__details');
    }
};
