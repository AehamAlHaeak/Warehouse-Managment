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
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
           
            $table->enum("type",["big","medium"]);
            $table->nullableMorphs("existable");//existable_type,existable_id
            //can be null only if the company decide to make undepended garage as a reserve
            //garage
            $table->string("location")->nullable();
            $table->double("latitude")->nullable();
            $table->double("longitude")->nullable();

            $table->bigInteger("max_capacity");
            /*we willnot add number of the vehicles to the garage we will take it
             from the realtions with the vehicles*/
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
