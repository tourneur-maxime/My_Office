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
        if (!Schema::hasColumn('invoice_line_items', 'sort_order')) {
            Schema::table('invoice_line_items', function (Blueprint $table) {
                $table->integer('sort_order')->default(0)->after('total');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('invoice_line_items', 'sort_order')) {
            Schema::table('invoice_line_items', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
