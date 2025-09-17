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
            $table->dropColumn('price_selector');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supported_assets', function (Blueprint $table) {
            $table->string('price_selector', 200)->nullable();
        });
    }
};
