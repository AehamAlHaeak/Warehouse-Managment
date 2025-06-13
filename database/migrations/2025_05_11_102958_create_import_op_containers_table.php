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
        Schema::create('import_op_containers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("container_type_id");
            $table->foreign("container_type_id")->references("id")->on("containers_types");
            $table->unsignedBigInteger("import_operation_id");
            $table->foreign("import_operation_id")->references("id")->on("import_operations");
           
            
            //when i make import operation i will enter the container and the products will 
            //be entered recently and linked with their containers
            // $table->unsignedBigInteger("transfer_vehicle_id")->nullable();
            // $table->foreign("transfer_vehicle_id")->references("id")->on("transfer__details");
            //i specifi the id by transfee_vehicle_id_to see this 
            //continer in which transfer has been loaded
             
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_op_containers');
    }
};
