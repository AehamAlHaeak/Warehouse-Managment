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
        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            //type specialized to the products as food,electronic,etc  and the cargos 
            //if we add the cargos as a undependent thing from vehicle
            //this feature allow to high quality managment and reduce the costs and more reality
            //we can consider it as a second action and we can replace it in any time 
            $table->string("name")->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('types');
    }
};
