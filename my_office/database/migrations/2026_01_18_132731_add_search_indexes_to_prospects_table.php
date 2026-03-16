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
        Schema::table('prospects', function (Blueprint $table) {
            // Add indexes for search optimization
            $table->index('user_id');
            $table->index('name');
            $table->index('company');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['user_id']);
            $table->dropIndex(['name']);
            $table->dropIndex(['company']);
            $table->dropIndex(['user_id', 'status']);
        });
    }
};
