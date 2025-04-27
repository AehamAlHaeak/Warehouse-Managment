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
        
        Schema::create('transfer__vehicle', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("vehicle_id");
            $table->unsignedBigInteger("transfer_id");
            $table->foreign("vehicle_id")->references("id")->on("vehicles");
            $table->foreign("transfer_id")->references("id")->on("transfers");
            $table->unsignedBigInteger("product_id");
            $table->foreign("product_id")->references("id")->on("products");
            $table->double("quantity_by_ton");
            $table->date("arrival_time");

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer__vehicle');
    }
};