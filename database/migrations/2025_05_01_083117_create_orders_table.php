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
            $table->integer('order_number')->unique();
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->tinyInteger('is_cart')->default(1);
            $table->unsignedBigInteger('address_id')->nullable();
            $table->foreign('address_id')->references('id')->on('addresses')->restrictOnDelete();
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->foreign('coupon_id')->references('id')->on('coupons')->restrictOnDelete();
            $table->integer('coupon_discount')->nullable();
            $table->json('address_info')->nullable();
            $table->string('notes',255)->nullable();
            $table->string('payment_method',50)->nullable();
            $table->decimal('delivery_amount', 10, 2)->nullable();
            $table->unsignedInteger('status')->default(0);
            $table->boolean('is_view')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
