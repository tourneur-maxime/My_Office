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
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Foreign key to users table
            $table->string('name');
            $table->string('company');
            $table->text('email'); // Storing encrypted, so use text
            $table->text('phone')->nullable(); // Encrypted, can be nullable
            $table->text('address')->nullable(); // Encrypted, can be nullable
            $table->string('status')->default('prospect'); // 'prospect' or 'client'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
