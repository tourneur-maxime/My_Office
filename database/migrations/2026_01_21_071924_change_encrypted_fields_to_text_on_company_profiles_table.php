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
            $table->text('address')->nullable()->change();
            $table->text('siret')->nullable()->change();
            $table->text('iban')->nullable()->change();
            $table->text('bic')->nullable()->change();
            $table->text('bank_name')->nullable()->change();
            $table->text('bank_account_holder')->nullable()->change();
            $table->text('email')->nullable()->change();
            $table->text('phone')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('address')->nullable()->change();
            $table->string('siret')->nullable()->change();
            $table->string('iban')->nullable()->change();
            $table->string('bic')->nullable()->change();
            $table->string('bank_name')->nullable()->change();
            $table->string('bank_account_holder')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->change();
        });
    }
};