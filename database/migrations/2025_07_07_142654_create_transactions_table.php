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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->date('transaction_date');
            $table->bigInteger('recurring_transaction_id')->nullable();
            $table->string('receipt_path', 500)->nullable();
            $table->text('notes')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'transaction_date']);
            $table->index(['account_id', 'transaction_date']);
            $table->index(['category_id', 'transaction_date']);
            $table->index(['type', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
