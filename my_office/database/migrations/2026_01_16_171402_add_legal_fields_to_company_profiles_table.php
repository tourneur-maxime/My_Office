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
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('siren')->nullable()->after('siret');
            $table->string('rcs_number')->nullable()->after('siren');
            $table->string('legal_form')->nullable()->after('rcs_number');
            $table->decimal('share_capital', 15, 2)->nullable()->after('legal_form');
            $table->string('payment_terms')->nullable()->after('share_capital');
            $table->boolean('is_vat_exempt')->default(false)->after('payment_terms');
            $table->text('custom_legal_mentions')->nullable()->after('is_vat_exempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'siren',
                'rcs_number',
                'legal_form',
                'share_capital',
                'payment_terms',
                'is_vat_exempt',
                'custom_legal_mentions',
            ]);
        });
    }
};
