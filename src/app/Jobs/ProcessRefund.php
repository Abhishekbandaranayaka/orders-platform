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
use App\Services\Analytics;

class ProcessRefund implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $refundId) {}
    public function viaQueue(): string { return 'refunds'; }

    public function handle(): void
    {
        $refund = DB::table('refunds')->where('id', $this->refundId)->first();
        if (!$refund) return;

        // If already processed/failed, idempotently exit
        if (in_array($refund->status, ['processed','failed'])) return;

        $order = Order::find($refund->order_id);
        if (!$order) {
            DB::table('refunds')->where('id',$refund->id)->update(['status'=>'failed','updated_at'=>now()]);
            Log::warning('Refund failed: order missing', ['refund_id'=>$refund->id]);
            return;
        }

        $amount = (int) $refund->amount_cents;

        // Atomic invariant: refunded_cents + amount <= total_cents
        $affected = DB::table('orders')
            ->where('id', $order->id)
            ->whereRaw('(refunded_cents + ?) <= total_cents', [$amount])
            ->update([
                'refunded_cents' => DB::raw("refunded_cents + {$amount}"),
                // Optional: set payment_status if fully refunded
                'payment_status' => DB::raw("(CASE WHEN (refunded_cents + {$amount}) >= total_cents THEN 'refunded' ELSE payment_status END)"),
                'updated_at'     => now(),
            ]);

        if ($affected === 0) {
            // Over-refund attempt or race: mark failed safely
            DB::table('refunds')->where('id',$refund->id)->update(['status'=>'failed','updated_at'=>now()]);
            Log::warning('Refund blocked (would exceed total)', ['refund_id'=>$refund->id,'order_id'=>$order->id]);
            return;
        }

        // Idempotent analytics decrement using a once-token keyed by refund id
        $analytics = new Analytics();
        $token = "refund:apply:{$refund->id}";
        if ($analytics->once($token)) {
            // Decrement revenue and leaderboard
            $analytics->incRevenueCents(-$amount);
            $analytics->incrCustomerSpend($order->customer_id, -$amount);
            $analytics->recomputeAov(); // order_count unchanged
        }

        DB::table('refunds')->where('id',$refund->id)->update(['status'=>'processed','updated_at'=>now()]);
        Log::info('Refund processed', ['refund_id'=>$refund->id,'order_id'=>$order->id,'amount'=>$amount]);
        event(new \App\Events\RefundProcessed($refund->id, $order->id, $order->customer_id, $amount));

    }
}
