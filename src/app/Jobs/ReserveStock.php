<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Services\Inventory;
use App\Jobs\SimulatePayment;

class ReserveStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $orderId) {}

    public function viaQueue(): string { return 'orders'; }

    public function handle(): void
    {
        $order = Order::with('items')->find($this->orderId);
        if (!$order) return;

        // If already terminal, skip
        if (in_array($order->status, ['completed','failed'])) {
            Log::info('ReserveStock: terminal state, skipping', ['order_id'=>$order->id]);
            return;
        }

        $inv = new Inventory();

        try {
            DB::transaction(function () use ($order, $inv) {
                foreach ($order->items as $item) {
                    $sku = $item->sku;
                    $qty = (int)$item->qty;
                    $corr = (string)$order->id;

                    // Already reserved?
                    $existing = DB::table('stock_ledger')
                        ->where('sku', $sku)
                        ->where('reason', 'reserve')
                        ->where('correlation_id', $corr)
                        ->first();

                    if ($existing && (int)$existing->delta < 0) {
                        // Already reserved earlier for this order+sku
                        continue;
                    }

                    // Ensure key exists (optional)
                    $inv->ensure($sku, 0);

                    // Try to reserve
                    if (!$inv->reserve($sku, $qty)) {
                        throw new \RuntimeException("Insufficient stock for {$sku}");
                    }

                    // Write/overwrite reservation ledger entry to negative qty (idempotent)
                    DB::table('stock_ledger')->updateOrInsert(
                        ['sku'=>$sku, 'reason'=>'reserve', 'correlation_id'=>$corr],
                        [
                            'delta'      => -$qty,           // reservation is negative
                            'order_id'   => $order->id,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }

                // Move status if still processing/imported
                if (in_array($order->status, ['imported','processing'])) {
                    $order->update(['status' => 'processing']);
                }
            });
        } catch (\Throwable $e) {
            Log::warning('ReserveStock failed; scheduling rollback', [
                'order_id'=>$order->id,
                'error'=>$e->getMessage()
            ]);
            // On failure, roll back in a separate job
            FinalizeOrRollback::dispatch($order->id, false)->onQueue('orders');
            return;
        }

        // Continue to payment simulation (always after attempting reservations)
        Log::info('ReserveStock: dispatching SimulatePayment', ['order_id'=>$order->id]);
        SimulatePayment::dispatch($order->id)->onQueue('payments');
    }
}
