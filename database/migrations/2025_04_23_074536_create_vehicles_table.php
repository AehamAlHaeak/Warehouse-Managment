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
            $table->double("max_load");
            $table->string("location");
            $table->double("latitude");
            $table->double("longitude");

            $table->string("img_path")->nullable();

            $table->enum("status",["under_work","finished","wait"]);
            

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
