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
        Schema::create('reserved_detail', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
             $table->unsignedBigInteger("transfer_details_id");
            $table->foreign("transfer_details_id")->references("id")->on("transfer__details");
            $table->integer("reserved_load");
            $table->unsignedBigInteger("imp_cont_prod_id");
            $table->foreign("imp_cont_prod_id")->references("id")->on("imp_continer_products");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserved_detail');
    }
};
