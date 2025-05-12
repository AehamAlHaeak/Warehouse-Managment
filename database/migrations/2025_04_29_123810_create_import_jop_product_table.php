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
        Schema::create('import_jop_product', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("import_jop_id");
            $table->foreign("import_jop_id")->references("id")->on("import_jops");
            $table->unsignedBigInteger("product_id");
            $table->foreign("product_id")->references("id")->on("products");
            $table->date("expiration");
            $table->date("producted_in");

         //   $table->string("unit");

            $table->string("unit");
            $table->double("actual_load");

            $table->double("price_unit");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_jop_product');
    }
};
