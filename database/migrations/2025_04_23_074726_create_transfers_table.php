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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->morphs("sourceable");
            $table->morphs("destinationable");
            //the date is important for the vehicle tasks

            $table->date("date_of_resiving");
            $table->date("date_of_finishing")->nullable();

            $table->string("location")->nullable();
            $table->double("latitude")->nullable();
            $table->double("longitude")->nullable();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
/**very importatnt notes: the dates here are very important
 we will create the transfer with date of resiving and date of finishing to deside if the vehicle
 will enter in this transfer or not by check the table transfer_vehicle
 if the vehicle is under work or not or have a tasks or not that will ocure by a column status in it
 */
