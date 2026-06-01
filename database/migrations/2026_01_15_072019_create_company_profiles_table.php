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
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('siret')->nullable();
            $table->string('iban')->nullable();
            $table->string('vat_number')->nullable(); // Added this line
            $table->string('vat_status')->default('assujetti');
            $table->string('invoice_numbering_format')->default('FACT-{YEAR}-{SEQUENCE}');
            $table->string('quote_numbering_format')->default('QUO-{YEAR}-{SEQUENCE}');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
