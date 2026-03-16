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
            // Drop FK first so MySQL allows dropping the unique index
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'pattern']);

            $table->string('prefix')->nullable()->default('FAC')->after('user_id');
            $table->string('suffix')->nullable()->after('prefix');
            $table->unsignedTinyInteger('digit_count')->default(4)->after('suffix');
            $table->integer('counter_year')->nullable()->after('current_number');

            // Drop pattern as it will be constructed dynamically
            $table->dropColumn('pattern');
        });

        // Re-add FK and unique constraint
        Schema::table('invoice_numbers', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_numbers', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropColumn(['prefix', 'suffix', 'digit_count', 'counter_year']);
            $table->string('pattern')->default('FAC-{YYYY}-{number:4}');
            $table->unique(['user_id', 'pattern']);
        });
    }
};