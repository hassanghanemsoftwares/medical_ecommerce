<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();

            // Link to product (for products without variants)
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();

            // Link to variant (for products with variants)
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->foreign('variant_id')->references('id')->on('variants')->restrictOnDelete();

            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('shelf_id')->constrained()->restrictOnDelete();

            $table->tinyInteger('adjustment_type');

            $table->unsignedInteger('quantity');
            $table->decimal('cost_per_item', 10, 2);
            $table->string('reason')->nullable();

            $table->foreignId('adjusted_by')->nullable()->constrained('users')->restrictOnDelete();

            $table->enum('reference_type', [
                'purchase_order',
                'sales_order',
                'return_from_customer',
                'return_to_supplier',
                'transfer_order',
                'inventory_count',
                'adjustment_note'
            ]);
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
