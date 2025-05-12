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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("name");
            $table->double("temperature");
            $table->double("humidity");
            $table->double("light");
            $table->double("pressure");
            $table->double("ventilation");
            $table->double("readyness");
            $table->morphs("existable");
            // at emergency case , we'll move the products from the usual storage to the emeregeny section 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
