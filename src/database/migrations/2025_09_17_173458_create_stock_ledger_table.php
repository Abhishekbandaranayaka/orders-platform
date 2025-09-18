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
        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();

            $table->string('sku')->index();
            $table->integer('delta'); // reservation: negative; release/compensation: positive
            $table->string('reason')->index(); // reserve|finalize|rollback
            $table->unsignedBigInteger('order_id')->nullable()->index();
    
            // Prevent double-reserve per order/sku/reason (idempotency)
            $table->string('correlation_id')->nullable()->index(); // usually order_id or composed key
            $table->unique(['sku','reason','correlation_id']);
    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
    }
};
