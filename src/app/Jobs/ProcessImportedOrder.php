<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Jobs\ReserveStock;


class ProcessImportedOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $orderId) {}

    public function viaQueue(): string { return 'import'; }

    public function handle(): void
    {
        $order = Order::with('items')->find($this->orderId);
        if (!$order) return;
    
        if ($order->status === 'imported') {
            $order->update(['status' => 'processing']);
        }
    
        // Next: reserve stock
        ReserveStock::dispatch($order->id)->onQueue('orders');
    }
    
}
