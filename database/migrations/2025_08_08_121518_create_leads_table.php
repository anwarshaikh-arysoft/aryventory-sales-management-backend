<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_to');
            $table->unsignedBigInteger('last_updated_by');
            $table->string('shop_name');
            $table->string('contact_person');
            $table->string('mobile_number');
            $table->string('alternate_number')->nullable();
            $table->string('email')->nullable();
            $table->string('address');
            $table->string('area_locality');
            $table->string('pincode');
            $table->string('gps_location');
            $table->string('business_type');
            $table->string('monthly_sales_volume');
            $table->string('current_system');
            $table->string('lead_status');
            $table->string('plan_interest');
            $table->date('next_follow_up_date');
            $table->text('meeting_notes')->nullable();
            $table->unsignedTinyInteger('prospect_rating');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
