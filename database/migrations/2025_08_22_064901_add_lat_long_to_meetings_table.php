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
        Schema::table('meetings', function (Blueprint $table) {
            $table->decimal('meeting_start_latitude', 10, 7)->nullable()->after('id');
            $table->decimal('meeting_start_longitude', 10, 7)->nullable()->after('meeting_start_latitude');
            $table->decimal('meeting_end_latitude', 10, 7)->nullable()->after('meeting_start_longitude');
            $table->decimal('meeting_end_longitude', 10, 7)->nullable()->after('meeting_end_latitude');
            $table->string('meeting_end_notes')->nullable()->after('meeting_end_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn([
                'meeting_start_latitude',
                'meeting_start_longitude',
                'meeting_end_latitude',
                'meeting_end_longitude',
                'meeting_end_notes',
            ]);
        });
    }
};
