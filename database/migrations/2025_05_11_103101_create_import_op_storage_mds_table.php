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
        Schema::create('import_op_storage_mds', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("storage_media_id");
            $table->foreign("storage_media_id")->references("id")->on("storage_media");


            $table->unsignedBigInteger("import_operation_id");

            $table->foreign("import_operation_id")->references("id")->on("import_operations");
            //required is external ones to let the storage media works
            $table->double("required_temperature")->nullable();

            $table->double("required_humidity")->nullable();
            $table->double("required_light")->nullable();
            $table->double("required_pressure")->nullable();
            $table->double("required_ventilation")->nullable();

            $table->morphs("existable");

            $table->integer("columns");
            $table->integer("rows");
            $table->double("readyness");
            // whatever was the readyness , when it became lower than 0.7 , we'll make a maintenance operation
            //and we'll take the products whatever they are .. on vehicles or storage media or section .. take them 
            // take them to the emergency section

            $table->double("internal_temperature")->default(25);
            $table->double("internal_humidity")->default(2);
            $table->double("internal_light")->default(3);
            $table->double("internal_pressure")->default(1);
            $table->double("internal_ventilation")->default(1);
            
          
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_op_storage_mds');
    }
};
