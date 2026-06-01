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
            $table->string('separator')->default('-')->after('suffix');
            $table->boolean('include_year')->default(true)->after('separator');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_numbers', function (Blueprint $table) {
            $table->dropColumn(['separator', 'include_year']);
        });
    }
};