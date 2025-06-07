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
            $table->integer("num_sections");
           
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
