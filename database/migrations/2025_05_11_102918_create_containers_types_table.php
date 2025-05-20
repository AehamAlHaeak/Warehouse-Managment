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
        Schema::create('containers_types', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("name");
            
            $table->unsignedBigInteger("product_id")->unique();
            $table->foreign("product_id")->references("id")->on("products");
            $table->integer("capacity");
            //contain by unit as example the thigh by unit the caocity is refers to number of it  
            // the container is a general concept , then we'll specify it
            // for an examplle : freezer , scaffold ,etc... 
            //that depends on the products I'll support it
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('containers_types');
    }
};
