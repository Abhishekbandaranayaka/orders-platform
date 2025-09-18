<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $outboxId) {}

    public function viaQueue(): string { return 'notifications'; }

    public function handle(): void
    {
        $row = DB::table('notifications_outbox')->where('id', $this->outboxId)->first();
        if (!$row) return;

        if ($row->status === 'sent') return; // idempotent

        try {
            $payload = json_decode($row->payload_json, true) ?: [];

            if ($row->channel === 'mail') {
                // Derive recipient; for demo use customer email if known, else a sink address
                $email = DB::table('customers')->where('id', $row->customer_id)->value('email') ?: 'sink@example.test';
                \Mail::to($email)->send(new \App\Mail\OrderStatusMail(
                    orderId: (int)($payload['order_id'] ?? 0),
                    customerId: (int)($payload['customer_id'] ?? 0),
                    status: (string)($payload['status'] ?? 'unknown'),
                    paymentStatus: (string)($payload['payment'] ?? 'unknown'),
                    totalCents: (int)($payload['total_cents'] ?? 0),
                ));
            } else {
                // default: log
                \Log::info('[NOTIFY] Order processed', $payload);
            }

            DB::table('notifications_outbox')->where('id', $this->outboxId)
                ->update(['status' => 'sent', 'updated_at' => now()]);
        } catch (\Throwable $e) {
            DB::table('notifications_outbox')->where('id', $this->outboxId)
                ->update(['status' => 'failed', 'error' => $e->getMessage(), 'updated_at' => now()]);
            throw $e;
        }
    }

}
