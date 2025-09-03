<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Simple string/date fields can use ->change()
            $table->string('contact_person')->nullable()->change();
            $table->string('plan_interest')->nullable()->change();
            $table->date('next_follow_up_date')->nullable()->change();
        });

        // For bigint (FK) fields in PostgreSQL, drop NOT NULL constraint manually
        DB::statement('ALTER TABLE leads ALTER COLUMN business_type DROP NOT NULL');
        DB::statement('ALTER TABLE leads ALTER COLUMN current_system DROP NOT NULL');
        DB::statement('ALTER TABLE leads ALTER COLUMN lead_status DROP NOT NULL');
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Rollback string/date columns
            $table->string('contact_person')->nullable(false)->change();
            $table->string('plan_interest')->nullable(false)->change();
            $table->date('next_follow_up_date')->nullable(false)->change();
        });

        // Rollback bigint fields to NOT NULL
        DB::statement('ALTER TABLE leads ALTER COLUMN business_type SET NOT NULL');
        DB::statement('ALTER TABLE leads ALTER COLUMN current_system SET NOT NULL');
        DB::statement('ALTER TABLE leads ALTER COLUMN lead_status SET NOT NULL');
    }
};
