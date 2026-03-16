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
        Schema::table('invoice_numbers', function (Blueprint $table) {
            $table->timestamp('last_generated_at')->nullable()->after('current_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_numbers', function (Blueprint $table) {
            $table->dropColumn('last_generated_at');
        });
    }
};
