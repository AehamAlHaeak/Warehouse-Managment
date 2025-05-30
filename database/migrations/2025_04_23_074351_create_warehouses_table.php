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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
           
            $table->string("name");
            $table->string("location");
            $table->double("latitude");
            $table->double("longitude");
            $table->enum("status", ["auto", "reject", "manual"])->default("auto");
            $table->integer("num_sections");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
