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
        Schema::create('distribution_center_product_details', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("dist_center_prod_id");
     
            $table->foreign("dist_center_prod_id")->references("id")->on("distribution_center__product");
           
            $table->unsignedBigInteger("import_op_prod_id");
            $table->foreign("import_op_prod_id")->references("id")->on("import_operation_product");
            $table->double("actual_load");
                });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_center__product_detail');
    }
};
