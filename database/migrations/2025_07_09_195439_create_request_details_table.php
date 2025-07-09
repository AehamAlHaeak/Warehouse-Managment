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
        Schema::create('request_details', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->morphs("requistable");
            $table->bigInteger("quantity");
            $table->unsignedBigInteger("request_id");
            $table->foreign("request_id")->references("id")->on("requests");
            $table->unsignedBigInteger("responceable_id");
            $table->foreign("responceable_id")->references("id")->on("employes");
            $table->enum("status",["responsed","remined"])->default("remined")->nullable();
            $table->bigInteger("responsed_quantity")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_details');
    }
};
