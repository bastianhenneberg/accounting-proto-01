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
        Schema::table('accounts', function (Blueprint $table) {
            // Add crypto support fields
            $table->string('crypto_symbol', 10)->nullable()->after('currency');
            $table->decimal('crypto_balance', 20, 8)->nullable()->after('crypto_symbol');
            $table->decimal('fiat_value', 15, 2)->nullable()->after('crypto_balance');
            $table->decimal('current_price', 15, 8)->nullable()->after('fiat_value');
            $table->timestamp('last_price_update')->nullable()->after('current_price');

            // Update type enum to include crypto
            $table->enum('type', ['checking', 'savings', 'credit_card', 'cash', 'investment', 'crypto'])->change();

            // Add index for crypto accounts
            $table->index(['type', 'crypto_symbol']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'crypto_symbol',
                'crypto_balance',
                'fiat_value',
                'current_price',
                'last_price_update'
            ]);

            $table->dropIndex(['type', 'crypto_symbol']);

            // Restore original enum
            $table->enum('type', ['checking', 'savings', 'credit_card', 'cash', 'investment'])->change();
        });
    }
};
