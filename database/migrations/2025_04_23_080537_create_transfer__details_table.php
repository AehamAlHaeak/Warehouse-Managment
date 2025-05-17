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
        
        Schema::create('transfer__details', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger("vehicle_id");
            $table->unsignedBigInteger("transfer_id");
            $table->foreign("vehicle_id")->references("id")->on("vehicles");
            $table->foreign("transfer_id")->references("id")->on("transfers");
          
 $table->enum("status",["under_work","received","wait","in_QA","Unloading","Packing"]);
            
            
   
             
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer__details');
    }
};
/**status is a managment fild why???:
 because when i will add transfer work i will see this table and decide who the vehicles can work?
 when i add a new transfer and add a vehivle in it will send a notification for the driver you have
 a task and send the details i will add the vehicle with the detils product_id quantity etc.. 
 when i see there is no any conflicts
 betwen the tasks and willnot reserve the vehicle while it can work another job !!! that is not regular
 then i will reserve the vehicle without conflicts and without to high cost to buy new vehicles 
 the vehicles are full time!!
 */