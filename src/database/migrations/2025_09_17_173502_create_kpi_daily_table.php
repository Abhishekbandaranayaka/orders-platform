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
        Schema::create('kpi_daily', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedBigInteger('revenue_cents')->default(0);
            $table->unsignedInteger('order_count')->default(0);
            $table->unsignedBigInteger('aov_cents')->default(0); // derived = revenue / count (stored for fast read)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_daily');
    }
};
