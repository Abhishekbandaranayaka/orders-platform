<?php

namespace App\Listeners;

use App\Events\OrderProcessed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendNotification;

class QueueNotificationOnOrderProcessed
{
    public function __construct() {}

    public function handle(OrderProcessed $event): void
    {
        // Outbox idempotency key: order + status + channel
        $channel = 'mail';
        $dedupe = "order:{$event->orderId}:{$event->status}:{$channel}";

        $payload = [
            'order_id'    => $event->orderId,
            'customer_id' => $event->customerId,
            'status'      => $event->status,
            'payment'     => $event->paymentStatus,
            'total_cents' => $event->totalCents,
            'ts'          => now()->toISOString(),
        ];

        // Insert if not exists
        $id = DB::table('notifications_outbox')->where('dedupe_key', $dedupe)->value('id');
        if (!$id) {
            $id = DB::table('notifications_outbox')->insertGetId([
                'order_id'     => $event->orderId,
                'customer_id'  => $event->customerId,
                'channel'      => $channel,
                'status'       => 'queued',
                'dedupe_key'   => $dedupe,
                'payload_json' => json_encode($payload),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // Enqueue sender
            SendNotification::dispatch($id)->onQueue('notifications');
        }
    }
}
