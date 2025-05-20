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
        Schema::create('import_operation_product', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("import_operation_id");
            $table->foreign("import_operation_id")->references("id")->on("import_operations");
            $table->unsignedBigInteger("product_id");
            $table->foreign("product_id")->references("id")->on("products");
            $table->date("expiration");
            $table->date("producted_in");

           
            $table->double("imported_load")->default(1);//refers to the load of this feature


       

            $table->string("special_description")->nullable();//if it exist then the load mostnot exist
            $table->double("price_unit");//price of buy 
   
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_operation_product');
    }
};
