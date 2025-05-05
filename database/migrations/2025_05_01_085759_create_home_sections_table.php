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
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // banner, about_us, team, product_section
            $table->string('title')->nullable(); // optional section title
            $table->json('content')->nullable(); // flexible data: images, text, product IDs, etc.
            $table->integer('arrangement')->default(0); // arrangement on homepage
            $table->boolean('is_active')->default(true); // show/hide on homepage
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};