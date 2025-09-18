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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents'); // partial or full
            $table->string('status')->default('queued'); // queued|processed|failed
            $table->string('idempotency_key')->unique(); // e.g., order_id + refund_uuid or order_id + amount hash
    
            $table->json('meta')->nullable();
            $table->timestamps();
    
            $table->index(['order_id','status','created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
