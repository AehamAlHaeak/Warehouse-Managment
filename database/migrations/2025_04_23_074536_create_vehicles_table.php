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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("name");
            $table->date("expiration");
            $table->date("producted_in");
            $table->double("readiness");
            $table->enum("size_of_vehicle",["big","medium"]);
            $table->string("location");
            $table->double("latitude");
            $table->double("longitude");
            $table->unsignedBigInteger("driver_id")->nullable();
            $table->foreign("driver_id")->references("id")->on("employes");
            $table->string("img_path")->nullable();

            $table->integer("capacity");
            //capacity reffers to the max number of the continers which it can load it


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
