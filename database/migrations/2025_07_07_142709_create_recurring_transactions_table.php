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
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_execution_date');
            $table->date('last_execution_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'is_active', 'next_execution_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
