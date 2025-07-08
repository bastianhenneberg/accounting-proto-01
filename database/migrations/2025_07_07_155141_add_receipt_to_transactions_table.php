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
        Schema::table('transactions', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('transactions', 'receipt_path')) {
                $table->string('receipt_path')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('transactions', 'receipt_filename')) {
                $table->string('receipt_filename')->nullable()->after('receipt_path');
            }
            if (!Schema::hasColumn('transactions', 'receipt_size')) {
                $table->integer('receipt_size')->nullable()->after('receipt_filename');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['receipt_path', 'receipt_filename', 'receipt_size']);
        });
    }
};
