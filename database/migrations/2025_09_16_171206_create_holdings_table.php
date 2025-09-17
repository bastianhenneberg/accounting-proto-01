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
        Schema::create('holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->enum('asset_type', ['crypto', 'stock', 'etf', 'bond', 'commodity']);
            $table->string('symbol', 20); // BTC, AAPL, MSCI_WORLD, etc.
            $table->string('name', 100); // Bitcoin, Apple Inc., MSCI World ETF
            $table->decimal('quantity', 20, 8); // Amount held
            $table->decimal('average_cost', 15, 8)->nullable(); // Average purchase price
            $table->decimal('current_price', 15, 8)->nullable(); // Current market price
            $table->decimal('market_value', 15, 2)->nullable(); // Current total value
            $table->decimal('total_invested', 15, 2)->default(0); // Total EUR invested
            $table->decimal('unrealized_pnl', 15, 2)->default(0); // Profit/Loss
            $table->timestamp('last_price_update')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'symbol']);
            $table->index(['asset_type', 'symbol']);
            $table->index(['account_id', 'asset_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holdings');
    }
};
