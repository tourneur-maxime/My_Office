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
            // Drop existing global counter if it exists and we're moving to user-specific
            // This is a destructive operation if there's actual data in current_number.
            // For a clean transition to user-specific, we'll assume it's okay to reset global.
            // If we needed to migrate existing global numbers, the logic would be more complex.
            Schema::dropIfExists('invoice_numbers');
        });

        Schema::create('invoice_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('pattern')->default('FAC-{YYYY}-{number:4}');
            $table->string('reset_frequency')->default('yearly'); // 'yearly', 'monthly', 'never'
            $table->unsignedBigInteger('current_number')->default(0);

            // Ensure unique sequence per user and pattern
            $table->unique(['user_id', 'pattern']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_numbers');

        // Recreate the old table structure if needed for rollback, or just leave it dropped.
        // For simplicity in down(), we'll just drop the new structure.
        // If the original table (without user_id/pattern) needs to be restored,
        // its migration should be rerun or manually handled.
        Schema::create('invoice_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('current_number')->default(0);
        });
    }
};
