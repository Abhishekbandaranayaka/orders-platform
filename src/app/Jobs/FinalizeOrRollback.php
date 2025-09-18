<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Services\Inventory;
use App\Events\OrderProcessed;

class FinalizeOrRollback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $orderId, public bool $success) {}

    public function viaQueue(): string { return 'orders'; }

    public function handle(): void
    {
        $order = Order::with('items')->find($this->orderId);
        if (!$order) return;

        // Idempotency guard: if already terminal state, exit
        if (in_array($order->status, ['completed','failed'])) return;

        if ($this->success) {
            DB::transaction(function () use ($order) {
                $order->update(['status'=>'completed','payment_status'=>'paid']);
                event(new OrderProcessed($order->id, $order->customer_id, 'completed', 'paid', (int)$order->total_cents));

                // (Step 5) We will emit domain events here for notifications & KPIs.
            });
            Log::info('Order finalized', ['order_id'=>$order->id]);
        } else {
            // Roll back reservations in ledger & inventory
            $inv = new Inventory();
            DB::transaction(function () use ($order, $inv) {
                foreach ($order->items as $item) {
                    $sku = $item->sku; $qty = (int) $item->qty;
                    // Release inventory (compensation)
                    $inv->release($sku, $qty);

                    // Write compensation entry if not exists
                    DB::table('stock_ledger')->updateOrInsert(
                        ['sku'=>$sku, 'reason'=>'rollback', 'correlation_id'=>(string)$order->id],
                        ['delta'=> $qty, 'order_id'=>$order->id, 'updated_at'=>now(), 'created_at'=>now()]
                    );
                }
                $order->update(['status'=>'failed','payment_status'=>'failed']);
                event(new OrderProcessed($order->id, $order->customer_id, 'failed', 'failed', (int)$order->total_cents));

            });
            Log::info('Order rolled back', ['order_id'=>$order->id]);
        }
    }
}
