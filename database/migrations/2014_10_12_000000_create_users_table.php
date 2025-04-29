<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\table;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id()->unique();//id is unique for any row in dataBase
     
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
          
            $table->unsignedBigInteger('phone_number')->unique()->nullable();
            
            /*this step can alow us to make the app more sequre
            id the user has been loged in rhe user can to enter to his account without recive the token 
            and to enhance the authontication system

           */ 
            
           $table->string("img_path")->nullable();//images will be stored in storage and we will organize a file for the images
            $table->rememberToken();/*this step can alow us to make the app more sequre
            id the user has been loged in rhe user can to enter to his account without recive the token 
            and to enhance the authontication system

           */ 

            $table->timestamps();
            $table->string('location');
            $table->json('criditCard')->nullable();//the user may have more than one cridit card in several banks
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
