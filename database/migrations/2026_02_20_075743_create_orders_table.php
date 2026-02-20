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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('shipping_amount', 10, 2);
            $table->decimal('final_total', 10, 2);
            
            $table->string('coupon_code_used')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('price_at_purchase', 10, 2);
            $table->integer('quantity');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
