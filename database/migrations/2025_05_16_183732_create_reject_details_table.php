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
        Schema::create('reject_details', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("employe_id")->nullable();
            $table->foreign("employe_id")->references("id")->on("employes");
            $table->integer("rejected_load");
            $table->unsignedBigInteger("imp_cont_prod_id");
            $table->foreign("imp_cont_prod_id")->references("id")->on("imp_continer_products");
            $table->text("why");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reject_details');
    }
};
