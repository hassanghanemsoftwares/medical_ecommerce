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
        Schema::table('client_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('token_id')->nullable()->after('client_id');
            $table->foreign('token_id')->references('id')->on('personal_access_tokens')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_sessions', function (Blueprint $table) {
            $table->dropForeign(['token_id']);
            $table->dropColumn('token_id');
        });
    }
};
