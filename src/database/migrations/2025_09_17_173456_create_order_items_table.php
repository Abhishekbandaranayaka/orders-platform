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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
    
            $table->string('sku')->index();
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('price_cents'); // unit price
            $table->unsignedBigInteger('line_total_cents'); // qty * price
    
            $table->timestamps();
    
            $table->index(['order_id','sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
