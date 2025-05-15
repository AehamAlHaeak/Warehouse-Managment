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
        Schema::create('storage_media', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->unsignedBigInteger("container_id");
            $table->foreign("container_id")->references("id")->on("containers_types");
             $table->integer("num_floors");
            $table->integer("num_classes");//row
            $table->integer("num_positions_on_class");//column
            //according to this container type , I'll know the products that'll be stored in it
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_media');
    }
};
