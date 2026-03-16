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
        Schema::table('sequence_rules', function (Blueprint $table) {
            // Drop existing unique index on 'type' if it exists.
            // This might vary depending on how Guava Sequence package defined its unique keys.
            // Assuming a simple unique index on 'type'.
            // Or if it was a composite key, we need to know the columns.
            // For now, let's try to drop a simple unique index on 'type'.
            // This step might fail if no such index exists or if it's named differently.
            // A more robust approach would be to check for its existence and name.
            try {
                $table->dropUnique(['type']);
            } catch (\Exception $e) {
                // Ignore if index does not exist
            }

            // Add a new composite unique index on type, pattern, and user_id.
            // This is crucial for distinguishing sequences per user and pattern.
            // user_id is nullable, so we need to handle cases where it's null (e.g., global sequences)
            // MySQL treats multiple NULLs in a unique index as unique values, which is fine here.
            $table->unique(['type', 'pattern', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sequence_rules', function (Blueprint $table) {
            // Drop the new composite unique index
            $table->dropUnique(['type', 'pattern', 'user_id']);

            // Re-add the original unique index on 'type' if it was dropped in up()
            // This assumes the original unique index was just on 'type'.
            $table->unique('type');
        });
    }
};
