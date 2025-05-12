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
        Schema::create('distribution_centers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("name");
            $table->string("location");
            $table->double("latitude");
            $table->double("longitude");
            $table->unsignedBigInteger("warehouse_id");
            $table->foreign("warehouse_id")->references("id")->on('warehouses');
          
            //we will not connect the center with the warehouse then the center will send a notifiction
            //to all warehouses then the warehouse who can send will send 
            //the center can recieve more than transfers in same time
            //that means the comunication and the mechanism will be changed
            //we need to rebuild some concepts 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_centers');
    }
};
