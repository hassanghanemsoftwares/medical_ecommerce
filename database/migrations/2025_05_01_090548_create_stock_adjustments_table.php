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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->foreign('variant_id')->references('id')->on('variants')->restrictOnDelete();

            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();

            $table->foreignId('shelf_id')->constrained()->restrictOnDelete();

            $table->tinyInteger('adjustment_type');

            $table->unsignedInteger('quantity');
            $table->decimal('cost_per_item', 10, 2);
            $table->string('reason')->nullable();
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('reference_type', [
                'purchase_order', // Stock increase from supplier purchase +
                'sales_order', //Stock decrease due to customer sale -
                'return_from_customer', // Stock increase from customer returns +
                'return_to_supplier', // Stock decrease when returning goods to supplier -
                'transfer_order', // Stock moved between warehouses or shelves + -
                'inventory_count', //Stock corrected after physical inventory check +
                'adjustment_note' //Manual adjustment, e.g., due to damage, loss, or administrative correction -
            ]);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
