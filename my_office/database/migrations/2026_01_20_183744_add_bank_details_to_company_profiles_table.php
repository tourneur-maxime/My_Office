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
            $table->string('bank_name')->nullable();
            
            // iban already exists as string, change to text for encryption if needed, 
            // but for now I will just change it to text to be safe.
            // SQLite doesn't support change() well, but let's try.
            // Actually, if I can't change it easily in SQLite, I'll assume string is enough 
            // OR drop and recreate.
            // Since this is dev, I can just change it.
            // But wait, the previous migration created it.
            // I'll leave it as is if it exists, or change it.
            
            // To allow re-running, I should check if column exists, but migrations run sequentially.
            // I will use change() for iban.
            
            $table->text('iban')->nullable()->change();
            
            $table->text('bic')->nullable(); // Encrypted
            $table->string('bank_account_holder')->nullable();
            $table->text('default_payment_terms')->nullable();
            $table->integer('default_payment_delay_days')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bic',
                'bank_account_holder',
                'default_payment_terms',
                'default_payment_delay_days',
            ]);
            // Revert iban to string? Not strictly necessary.
        });
    }
};
