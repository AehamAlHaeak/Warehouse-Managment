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
        Schema::table('import_operation_product', function (Blueprint $table) {
            //import_op_containers
            $table->unsignedBigInteger("imp_op_contin_id");

            $table->foreign("imp_op_contin_id")->references("id")->on("import_op_containers");

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_operation_product', function (Blueprint $table) {
            $table->dropColumn("imp_op_contin_id");
        });
    }
};
