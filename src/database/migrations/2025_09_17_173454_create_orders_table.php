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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
    
            $table->string('status')->default('imported')->index();        // imported|processing|completed|failed
            $table->string('payment_status')->default('pending')->index(); // pending|paid|failed|refunded|partially_refunded
    
            $table->unsignedBigInteger('total_cents')->default(0);         // store money in cents
            $table->unsignedBigInteger('refunded_cents')->default(0);
    
            $table->string('source_key')->nullable()->unique();            // idempotency for CSV row (file:line or hash)
            $table->json('meta')->nullable();
    
            $table->timestamps();
    
            $table->index(['customer_id','created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
