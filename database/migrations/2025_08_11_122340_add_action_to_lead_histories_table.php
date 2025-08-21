<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_histories', function (Blueprint $table) {
            $table->string('action')->nullable()->after('lead_id'); 
            // nullable so existing rows aren't broken
        });
    }

    public function down(): void
    {
        Schema::table('lead_histories', function (Blueprint $table) {
            $table->dropColumn('action');
        });
    }
};
