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
        Schema::create('import_jops', function (Blueprint $table) {
            //this table is ame the bill but we most know this related with th supplier 
            //this include time of create and supplier who make it 
            //thin wee will full the import_jop_product by the details as the date of expiration
            //that is good to storage managment
            // i can to sell the oler product or the product which its expairation_time is near!
            //and i can to recive the problems from users about specified product
            //then i can to see who is the source and process the problem???
            // the relation will change but taht is not a big problem!!
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("supplier_id");
            $table->foreign("supplier_id")->references("id")->on("suppliers");
            $table->date("arrival_time")->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_jops');
    }
};
