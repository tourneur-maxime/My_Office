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
        Schema::table('quote_number_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('counter_year')->default((int) date('Y'))->after('last_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_number_settings', function (Blueprint $table) {
            $table->dropColumn('counter_year');
        });
    }
};
