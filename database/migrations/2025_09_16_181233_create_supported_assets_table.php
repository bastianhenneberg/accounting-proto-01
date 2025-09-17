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
        Schema::create('supported_assets', function (Blueprint $table) {
            $table->id();
            $table->enum('asset_type', ['crypto', 'stock', 'etf', 'bond', 'commodity']);
            $table->string('symbol', 20)->unique();
            $table->string('name', 100);
            $table->string('api_id', 50)->nullable(); // CoinGecko ID, Yahoo Finance symbol, etc.
            $table->string('price_url', 500)->nullable(); // URL to scrape price from
            $table->string('price_selector', 200)->nullable(); // CSS selector for price
            $table->string('currency', 3)->default('EUR');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Additional info (ISIN, etc.)
            $table->timestamps();

            $table->index(['asset_type', 'is_active']);
            $table->index(['symbol', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supported_assets');
    }
};
