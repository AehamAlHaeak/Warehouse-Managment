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
        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->morphs("violable");
            $table->enum("parameter",["temperature", "humidity", "light", "pressure", "ventilation"]);
            $table->unsignedBigInteger("job_id")->nullable();
            $table->enum('status', ['wait', 'handled', 'effected'])->default('wait');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('violations');
    }
};
