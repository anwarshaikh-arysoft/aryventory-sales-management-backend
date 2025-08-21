<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_daily_shifts', function (Blueprint $table) {
            // Add new columns
            $table->string('shift_start_selfie_image')->nullable()->after('shift_start');
            $table->string('shift_end_selfie_image')->nullable()->after('shift_end');
            $table->decimal('shift_start_latitude', 10, 7)->nullable()->after('shift_start_selfie_image');
            $table->decimal('shift_start_longitude', 10, 7)->nullable()->after('shift_start_latitude');
            $table->decimal('shift_end_latitude', 10, 7)->nullable()->after('shift_end_selfie_image');
            $table->decimal('shift_end_longitude', 10, 7)->nullable()->after('shift_end_latitude');

            // Remove old columns if not needed
            $table->dropColumn(['selfie_image', 'latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('user_daily_shifts', function (Blueprint $table) {
            // Re-add old columns
            $table->string('selfie_image')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Drop new columns
            $table->dropColumn([
                'shift_start_selfie_image',
                'shift_end_selfie_image',
                'shift_start_latitude',
                'shift_start_longitude',
                'shift_end_latitude',
                'shift_end_longitude',
            ]);
        });
    }
};
