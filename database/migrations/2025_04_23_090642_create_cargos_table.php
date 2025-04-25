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
        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("type_id");
            $table->foreign("type_id")->references("id")->on("types");
            $table->unsignedBigInteger("vehicle_id");
            $table->foreign("vehicle_id")->references("id")->on("vehicles");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargos');
    }
};
