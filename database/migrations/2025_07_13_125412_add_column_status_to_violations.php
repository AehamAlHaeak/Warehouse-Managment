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
        Schema::table('violations', function (Blueprint $table) {
         $table->dropForeign(['job_id']);

            
            $table->enum('status', ['wait', 'handled', 'effected'])->default('wait');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('violations', function (Blueprint $table) {
           $table->dropColumn('status');

            
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
        });
    }
};
