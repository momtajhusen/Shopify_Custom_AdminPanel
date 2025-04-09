<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('order_assignment_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_assignment_id'); 
            $table->decimal('vendor_price', 10, 2); 
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('rejection_reason')->nullable(); 
            $table->timestamps();
            $table->foreign('order_assignment_id')->references('id')->on('order_assignments')->onDelete('cascade');
        });
    }    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_assignment_prices');
    }
};
