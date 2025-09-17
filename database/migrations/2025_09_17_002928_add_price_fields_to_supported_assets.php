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
        Schema::table('supported_assets', function (Blueprint $table) {
            $table->decimal('current_price', 15, 8)->nullable()->after('price_url');
            $table->timestamp('last_price_update')->nullable()->after('current_price');
            $table->enum('price_source', ['api', 'manual', 'ai_extracted', 'none'])->default('none')->after('last_price_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supported_assets', function (Blueprint $table) {
            $table->dropColumn(['current_price', 'last_price_update', 'price_source']);
        });
    }
};
