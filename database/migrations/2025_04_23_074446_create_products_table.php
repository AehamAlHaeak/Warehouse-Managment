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

           

            $table->bigInteger("import_cycle")->nullable();
            $table->double("average")->default(0);
            //as a note we willnot store standard deviation because it sqrt(variance/n)
            $table->double("variance")->default(0);

            $table->double("lowest_temperature");
            $table->double("highest_temperature");

            $table->double("lowest_humidity");
            $table->double("highest_humidity");

            $table->double("lowest_light");
            $table->double("highest_light");

            $table->double("lowest_pressure");
            $table->double("highest_pressure");

            $table->double("lowest_ventilation");
            $table->double("highest_ventilation");
            

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
