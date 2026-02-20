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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->string('name');
            $table->string('sku')->unique(); // Unique identifier
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2); 
            $table->integer('stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            // Indexing
            $table->index(['category_id', 'is_active']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
