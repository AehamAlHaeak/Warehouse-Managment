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
        Schema::create('container_movments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("imp_op_cont_id");
            $table->foreign("imp_op_cont_id")->references("id")->on("import_op_containers");
            $table->unsignedBigInteger("prev_position_id");
            $table->foreign("prev_position_id")->references("id")->on("positions_on_sto_m");

           
            $table->text("moved_why");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('container_movments');
    }
};
