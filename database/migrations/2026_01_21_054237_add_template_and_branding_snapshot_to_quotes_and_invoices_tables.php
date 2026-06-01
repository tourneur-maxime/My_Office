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
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'branding_snapshot')) {
                $table->json('branding_snapshot')->nullable()->after('template_id');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'template_id')) {
                $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            }
            if (!Schema::hasColumn('invoices', 'branding_snapshot')) {
                $table->json('branding_snapshot')->nullable()->after('template_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('branding_snapshot');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_id');
            $table->dropColumn('branding_snapshot');
        });
    }
};
