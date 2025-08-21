<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('selfie_for_meetings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meeting_id');
            $table->string('media');
            $table->timestamps();

            // Optional: Add foreign key constraint if needed
            // $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('selfie_for_meetings');
    }
};
