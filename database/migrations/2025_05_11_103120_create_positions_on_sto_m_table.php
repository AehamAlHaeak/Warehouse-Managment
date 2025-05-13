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
        Schema::create('positions_on_sto_m', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            //import_op_storage_md_id
            $table->unsignedBigInteger("imp_op_stor_id");
            $table->foreign("imp_op_stor_id")->references("id")->on("import_op_storage_mds");

            $table->integer("class");//row
            $table->integer("positions_on_class");//column

            //import_op_containers
            $table->unsignedBigInteger("imp_op_conti_id")->nullable();
            $table->foreign("imp_op_conti_id")->references("id")->on("import_op_containers");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions_on_sto_m');
    }
};
