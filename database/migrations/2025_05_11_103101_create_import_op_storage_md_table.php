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
        Schema::create('import_op_storage_md', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("storage_media_id");
            $table->foreign("storage_media_id")->references("id")->on("storage_media");


            $table->unsignedBigInteger("import_operation_id");

            $table->foreign("import_operation_id")->references("id")->on("import_operations");
            //required is external ones to let the storage media works

            
          
            $table->double("readyness")->default(1);
            // whatever was the readyness , when it became lower than 0.7 , we'll make a maintenance operation
            //and we'll take the products whatever they are .. on vehicles or storage media or section .. take them 
            // take them to the emergency section

            $table->double("internal_temperature")->default(25);//celicios normal 20-29
            $table->double("internal_humidity")->default(2);//rate percent %
            $table->double("internal_light")->default(100);//lux normal is 100 on closed rooms
            $table->double("internal_pressure")->default(1);//atm default 1 atm
            $table->double("internal_ventilation")->default(9);//letr/second default is 8-10
            
          
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_op_storage_md');
    }
};
