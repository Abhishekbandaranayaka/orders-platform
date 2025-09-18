<?php

namespace App\Listeners;

use App\Events\RefundProcessed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendNotification;

class QueueNotificationOnRefundProcessed
{
    public function handle(RefundProcessed $e): void
    {
        $channel = 'log';
        $dedupe  = "refund:{$e->refundId}:{$channel}";

        $exists = DB::table('notifications_outbox')->where('dedupe_key',$dedupe)->value('id');
        if ($exists) return;

        $id = DB::table('notifications_outbox')->insertGetId([
            'order_id'     => $e->orderId,
            'customer_id'  => $e->customerId,
            'channel'      => $channel,
            'status'       => 'queued',
            'dedupe_key'   => $dedupe,
            'payload_json' => json_encode([
                'type'         => 'refund_processed',
                'refund_id'    => $e->refundId,
                'order_id'     => $e->orderId,
                'customer_id'  => $e->customerId,
                'amount_cents' => $e->amountCents,
                'ts'           => now()->toISOString(),
            ]),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        SendNotification::dispatch($id)->onQueue('notifications');
    }
}
