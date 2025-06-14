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
        Schema::create('continer_transfers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("transfer_detail_id")->nullable();
            $table->foreign("transfer_detail_id")->references("id")->on("transfer__details");
            $table->unsignedBigInteger("imp_op_contin_id");
            $table->foreign("imp_op_contin_id")->references("id")->on("import_op_containers");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('continer_transfers');
    }
};
