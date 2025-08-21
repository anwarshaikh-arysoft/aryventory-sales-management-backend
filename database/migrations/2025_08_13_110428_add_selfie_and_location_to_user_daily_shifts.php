<?php

// database/migrations/xxxx_xx_xx_add_selfie_and_location_to_user_daily_shifts.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('user_daily_shifts', function (Blueprint $table) {
            $table->string('selfie_image')->nullable(); // store path or URL
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
    }

    public function down(): void {
        Schema::table('user_daily_shifts', function (Blueprint $table) {
            $table->dropColumn(['selfie_image', 'latitude', 'longitude']);
        });
    }
};
