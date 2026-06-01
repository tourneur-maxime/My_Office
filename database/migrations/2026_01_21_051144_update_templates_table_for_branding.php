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
        Schema::table('templates', function (Blueprint $table) {
            $table->renameColumn('font', 'font_family');
            $table->string('logo_path')->nullable()->after('is_default');
            $table->integer('logo_size')->default(100)->after('logo_path');
            $table->string('logo_position')->default('left')->after('logo_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->renameColumn('font_family', 'font');
            $table->dropColumn(['logo_path', 'logo_size', 'logo_position']);
        });
    }
};