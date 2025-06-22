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
             $table->double("internal_temperature")->default(25);//celicios normal 20-29
            $table->double("internal_humidity")->default(2);//rate percent %
            $table->double("internal_light")->default(100);//lux normal is 100 on closed rooms
            $table->double("internal_pressure")->default(1);//atm default 1 atm
            $table->double("internal_ventilation")->default(9);//letr/second default is 8-10
            
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
