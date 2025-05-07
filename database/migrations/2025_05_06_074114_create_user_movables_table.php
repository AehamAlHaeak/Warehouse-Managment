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
        Schema::create('user_movables', function (Blueprint $table) {
            $table->id();
        
            // الزبون (المستلم)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        
        
            // مركز التوزيع الذي خرجت منه الشحنة
            $table->foreignId('distribution_center_id')->constrained('distribution_centers')->cascadeOnDelete();
        
            // المركبة التي تقوم بالشحن
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
        
            // الوجهة (عادة موقع الزبون)
            $table->json('destination'); // مثال: { "locationName": "منزل محمد", "latitude": 24.7, "longitude": 46.6 }
            $table->double('max_load');
        
            // وقت بدء الشحن
            $table->dateTime('transfer_time_starts');
        
            // وقت التسليم (فارغ إلى أن يتم التسليم فعليًا)
            $table->dateTime('shipment_delivery_time')->nullable();
        
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_movables');
    }
};
