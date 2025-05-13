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
        Schema::create('posetions_on_sections', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("section_id");
            $table->foreign("section_id")->references("id")->on("sections")->cascadeOnDelete();
            $table->integer("floor");
            $table->integer("class");//row
            $table->integer("positions_on_class");//column
            $table->unsignedBigInteger("stor_med_id")->nullable()->unique();//storage_media_id
            $table->foreign("sto_med_id")->references("id")->on("storage_media");
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posetions_on_sections');
    }
};
