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
        Schema::table('import_op_containers', function (Blueprint $table) {
              $table->unsignedBigInteger("imp_op_stor_m_id")->nullable()->unique();//import_op_sto_m_id
            $table->foreign("imp_op_stor_m_id")->references("id")->on("import_op_storage_mds");
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_op_containers', function (Blueprint $table) {
            $table->dropColumn("imp_op_stor_m_id");
        });
    }
};
