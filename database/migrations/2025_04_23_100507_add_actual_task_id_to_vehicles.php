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
        Schema::table('vehicles', function (Blueprint $table) {
           // $table->nullableMorphs("taskable");// taskable_type, taskable_id
        $table->unsignedBigInteger("transfer_id")->nullable();
        $table->foreign("transfer_id")->references("id")->on("transfers");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('taskable_type');
            $table->dropColumn("taskable_id");
        });
    }
};
