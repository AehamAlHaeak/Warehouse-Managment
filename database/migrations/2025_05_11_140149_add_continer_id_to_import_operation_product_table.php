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
        Schema::table('import_operation_product', function (Blueprint $table) {
           // import_op_containers where the product exist which continer???
            $table->unsignedBigInteger("imp_op_contin_id")->nullable();
             //when it is null the product is slled
            $table->foreign("imp_op_contin_id")->references("id")->on("import_op_containers");
            //the product may be in import_op_continer or on user that mean it salled
            $table->enum("status",["sold","rejected","accepted"])->default("accepted");
            //the status is for this product if soled i can see who by it,
            //if accepted i will see where does it exist,
            //if rejected i will see why?,
            //default it accepted 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_operation_product', function (Blueprint $table) {
            $table->dropColumn("imp_op_contin_id");
        });
    }
};
