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
        Schema::create('bill__details', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("product_id");
            $table->unsignedBigInteger("bill_id");
            $table->foreign("product_id")->references("id")->on("products");
            $table->foreign("bill_id")->references("id")->on("bills");
            $table->double("quantity");
            //we delete the table bill_vehicle because the details and this relation make the same task
            //and remove schemas from the DB and remove model and make the work more flexible
            $table->unsignedBigInteger("vehicle_id")->nullable();
            $table->foreign("vehicle_id")->references("id")->on("vehicles");
        });
    }
      
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill__details');
    }
};
