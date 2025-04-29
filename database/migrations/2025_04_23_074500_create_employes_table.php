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
        Schema::create('employes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("name");
            $table->string("email")->unique()->nullable();
            $table->string("password");
            $table->integer("phone_number")->nullable();
            $table->unsignedBigInteger("specialization_id");
            $table->foreign("specialization_id")->references("id")->on("specializations");
            $table->double("salary");
            $table->date("birth_day");
            $table->string("country");
            $table->time("start_time");
            $table->double("work_hours");
            $table->nullableMorphs('workable');//workable_type  workable_id
            $table->string("img_path")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employes');
    }
};
