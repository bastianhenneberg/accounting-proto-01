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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->enum('type', ['checking', 'savings', 'credit_card', 'cash', 'investment']);
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->string('currency', 3)->default('EUR');
            $table->string('account_number', 50)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
