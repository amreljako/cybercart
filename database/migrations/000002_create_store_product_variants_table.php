<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('store_products')->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('barcode')->unique()->nullable();
            $table->json('option_values')->nullable(); // {"color": "black", "size": "XL"}
            $table->decimal('price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->boolean('track_stock')->default(true);
            $table->timestamps();

            // Compound index optimized for high-speed dynamic attribute filtering
            $table->index(['product_id', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_product_variants');
    }
};