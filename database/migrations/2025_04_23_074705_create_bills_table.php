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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("distribution_center_id");
            $table->foreign("user_id")->references("id")->on("users");
            $table->foreign("distribution_center_id")->references("id")->on("distribution_centers");
            //we remove the table distribution_center_id because it is not important and the relation here
            //replace it the relation in real is one distribution center has many bills!! 
            $table->date("date_of_resiving");
            $table->date("date_of_finishing");
            $table->string("location");
            $table->double("latitude");
            $table->double("longitude");

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
