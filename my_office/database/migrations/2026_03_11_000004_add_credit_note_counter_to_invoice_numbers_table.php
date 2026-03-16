<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_numbers', function (Blueprint $table) {
            $table->unsignedInteger('current_credit_note_number')->default(0)->after('current_number');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_numbers', function (Blueprint $table) {
            $table->dropColumn('current_credit_note_number');
        });
    }
};
