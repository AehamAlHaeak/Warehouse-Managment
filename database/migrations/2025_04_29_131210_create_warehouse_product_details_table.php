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
        Schema::create('warehouse_product_details', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("warehouse_product_id");
            
            $table->foreign("warehouse_product_id")->references("id")->on("warehouse__product");
           
            $table->unsignedBigInteger("import_jop_product_id");
            $table->foreign("import_jop_product_id")->references("id")->on("import_jop_product");
            $table->double("actual_load");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('werehouse__product_detail');
    }
};
