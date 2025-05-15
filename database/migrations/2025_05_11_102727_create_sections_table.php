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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("name");
           
            $table->morphs("existable");
            $table->unsignedBigInteger("product_id");
            $table->foreign("product_id")->references("id")->on("products");
            $table->integer("num_floors");
            $table->integer("num_classes");//row
            $table->integer("num_positions_on_class");//column
            // at emergency case , we'll move the products from the usual storage to the emeregeny section 
             $table->double("average")->default(0);
            //as a note we willnot store standard deviation because it sqrt(variance/n)
            $table->double("variance")->default(0);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
