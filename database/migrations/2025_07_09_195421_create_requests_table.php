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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("asker_id");
            $table->foreign("asker_id")->references("id")->on("employes");
            $table->morphs("destinationable");
            $table->morphs("sourceable");
            $table->enum("request_type",["products","vehicles","storage_media"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
