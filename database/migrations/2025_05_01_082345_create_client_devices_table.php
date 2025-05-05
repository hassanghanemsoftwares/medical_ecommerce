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
        Schema::create('client_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->string('notification_token', 255)->nullable();
            $table->string('device_id')->unique();
            $table->string('ip_address')->nullable();
            $table->string('device_name')->nullable();
            $table->string('device_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_activity');
            $table->double('latitude', 10, 6)->nullable();
            $table->double('longitude', 10, 6)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_devices');
    }
};
