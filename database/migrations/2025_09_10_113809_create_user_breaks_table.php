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
        Schema::create('user_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_daily_shift_id')->constrained()->onDelete('cascade');
            $table->dateTime('break_start');
            $table->dateTime('break_end')->nullable();
            $table->integer('break_duration_mins')->nullable();
            $table->enum('break_type', ['lunch', 'coffee', 'personal', 'other'])->default('other');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index for better query performance
            $table->index(['user_daily_shift_id', 'break_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_breaks');
    }
};