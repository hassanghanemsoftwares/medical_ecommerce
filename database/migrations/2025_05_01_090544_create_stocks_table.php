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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('shelf_id')->nullable()->constrained()->restrictOnDelete();
            $table->integer('quantity')->default(0);
            $table->timestamps();
            $table->unique(['variant_id', 'warehouse_id', 'shelf_id'], 'stock_location_unique');
            $table->index('variant_id');
            $table->index('warehouse_id');
            $table->index('shelf_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
