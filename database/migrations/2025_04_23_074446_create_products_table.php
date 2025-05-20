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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("name");
            $table->text("description"); 

            $table->string("img_path")->nullable();
             
             $table->integer("quantity")->default(1);
             $table->string("unit");
           

            $table->double("actual_piece_price");//for all methods i will take the price from here
           
            $table->bigInteger("import_cycle")->nullable();
         
            $table->double("lowest_temperature")->nullable();
            $table->double("highest_temperature")->nullable();

            $table->double("lowest_humidity")->nullable();
            $table->double("highest_humidity")->nullable();

            $table->double("lowest_light")->nullable();
            $table->double("highest_light")->nullable();

            $table->double("lowest_pressure")->nullable();
            $table->double("highest_pressure")->nullable();

            $table->double("lowest_ventilation")->nullable();
            $table->double("highest_ventilation")->nullable();
            //where the fild is null then it isnot important or isnot a condition

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
