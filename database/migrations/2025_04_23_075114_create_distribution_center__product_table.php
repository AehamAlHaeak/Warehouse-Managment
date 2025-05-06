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
        Schema::create('distribution_center__product', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("distribution_center_id");
            $table->unsignedBigInteger("product_id");
            $table->foreign("distribution_center_id")->references("id")->on("distribution_centers");
            $table->foreign("product_id")->references("id")->on("products");
            $table->double("max_load");
           //$table->double("actual_load");//actual load is not important
            $table->double("average")->default(0);
            //as a note we willnot store standard deviation because it sqrt(variance/n)
            $table->double("variance")->default(0);
            //n is a unit in company is the import cycle time here is a week
            //in distrebution_center is a day 
            $table->unique(['distribution_center_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_center__product');
    }
};
