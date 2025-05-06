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
        Schema::table('transfer__vehicle', function (Blueprint $table) {
            $table->unsignedBigInteger("import_product_id");
            //import_product_id is import_jop_product_id
            $table->foreign("import_product_id")->references("id")->on("import_jops");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfer__vehicle', function (Blueprint $table) {
            $table->dropColumn("import_product_id");
        });
    }
};
