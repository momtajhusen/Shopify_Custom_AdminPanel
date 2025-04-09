<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_assignments', function (Blueprint $table) {
            $table->id();

            // Shopify order & product references
            $table->string('order_id'); // Shopify Order ID
            $table->string('product_id'); // Shopify Product ID
            $table->string('variant_id')->nullable(); // Shopify Variant ID (optional)

            // Vendor relation
            $table->unsignedBigInteger('vendor_id');

            // Pricing
            $table->decimal('vendor_price', 10, 2)->nullable();

            // AWB / Courier info (for shipped status)
            $table->string('awb_number')->nullable();
            $table->string('courier_company')->nullable();
            $table->date('dispatch_date')->nullable();
            $table->string('tracking_url')->nullable();

            // Status based on new client flow
            $table->enum('status', [
                'assigned',
                'accepted',
                'in_process',
                'ready',
                'shipped',
                'in_transit',
                'delivered'
            ])->default('assigned');

            $table->timestamps();

            // Foreign key constraint (assuming you have a vendors table)
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_assignments');
    }
};


