<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Order;

class RefundRequest extends Command
{
    protected $signature = 'refunds:request {orderId} {amount_cents} {--key=}';
    protected $description = 'Request a (partial/full) refund in cents; enqueues processing';

    public function handle(): int
    {
        $orderId = (int) $this->argument('orderId');
        $amount  = (int) $this->argument('amount_cents');
        $key     = $this->option('key') ?: ('ord'.$orderId.'-amt'.$amount.'-'.Str::uuid());

        if ($amount <= 0) { $this->error('Amount must be > 0'); return self::FAILURE; }

        $order = Order::find($orderId);
        if (!$order) { $this->error("Order {$orderId} not found"); return self::FAILURE; }

        // Insert refund row idempotently by unique idempotency_key
        $exists = DB::table('refunds')->where('idempotency_key', $key)->first();
        if ($exists) { $this->info("Refund already requested (id={$exists->id})"); return self::SUCCESS; }

        $id = DB::table('refunds')->insertGetId([
            'order_id'        => $order->id,
            'amount_cents'    => $amount,
            'status'          => 'queued',
            'idempotency_key' => $key,
            'meta'            => json_encode(['requested_at'=>now()->toISOString()]),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        \App\Jobs\ProcessRefund::dispatch($id)->onQueue('refunds');
        $this->info("Refund queued: id={$id}, key={$key}");
        return self::SUCCESS;
    }
}
