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
        Schema::create('notifications_outbox', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
    
            $table->string('channel')->default('log'); // log|mail|...
            $table->string('status')->default('queued'); // queued|sent|failed
            $table->string('dedupe_key')->nullable()->unique(); // order_id+status+channel
    
            $table->json('payload_json'); // includes order_id, customer_id, status, total_cents, etc.
            $table->text('error')->nullable();
    
            $table->timestamps();
    
            $table->index(['status','created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications_outbox');
    }
};
