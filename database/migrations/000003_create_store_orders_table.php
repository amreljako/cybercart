<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            
            // Polymorphic structure to bind with any existing Users/Clients table seamlessly
            $table->string('customer_type');
            $table->unsignedBigInteger('customer_id');
            
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_total', 12, 2)->default(0.00);
            $table->string('coupon_code')->nullable();
            $table->decimal('shipping_total', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2);

            $table->string('payment_driver'); // paymob, tamara, mada, etc.
            $table->string('payment_status')->default('pending')->index();
            $table->string('payment_transaction_id')->nullable()->unique();
            $table->string('order_status')->default('pending')->index(); // Driven by State Machine

            $table->json('shipping_address');
            $table->json('billing_address')->nullable();
            $table->timestamps();

            $table->index(['customer_type', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_orders');
    }
};