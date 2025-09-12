<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_breaks', function (Blueprint $table) {
            // Change from integer to decimal(8,4) → up to 999999.9999 minutes
            $table->decimal('break_duration_mins', 8, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_breaks', function (Blueprint $table) {
            // Rollback to integer
            $table->integer('break_duration_mins')->nullable()->change();
        });
    }
};
