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
        Schema::table('invoices', function (Blueprint $table) {
            $table->json('facturx_metadata')->nullable()->after('total');
            $table->string('pdf_path')->nullable()->after('facturx_metadata');
            $table->boolean('is_compliant')->default(false)->after('pdf_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['facturx_metadata', 'pdf_path', 'is_compliant']);
        });
    }
};