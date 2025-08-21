<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->dateTime('followup_date');
            $table->unsignedBigInteger('user_id');
            $table->text('notes');
            $table->string('status');
            $table->dateTime('created_at');

            // Optional: Uncomment if you want foreign key constraints
            // $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_follow_ups');
    }
};
