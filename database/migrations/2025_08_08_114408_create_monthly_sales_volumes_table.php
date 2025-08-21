<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthlySalesVolumesTable extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_sales_volumes', function (Blueprint $table) {
            $table->id();
            $table->string('volume');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_sales_volumes');
    }
}
