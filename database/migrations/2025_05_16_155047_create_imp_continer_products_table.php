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
        Schema::create('imp_continer_products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("imp_op_cont_id");
            $table->foreign("imp_op_cont_id")->references("id")->on("import_op_containers");
            $table->unsignedBigInteger("imp_op_product_id");
            $table->foreign("imp_op_product_id")->references("id")->on("import_operation_product");
            $table->integer("load")->default(0);//unable to modefi
            
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imp_continer_products');
    }
};
