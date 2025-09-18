<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Order;

class DebugOrderStatus extends Command
{
    protected $signature = 'debug:order-status {orderId}';
    protected $description = 'Show order status, items, and stock ledger rows';

    public function handle(): int
    {
        $id = (int)$this->argument('orderId');
        $o = Order::with('items','customer')->find($id);
        if (!$o) { $this->error("Order {$id} not found"); return self::FAILURE; }

        $this->line("Order #{$o->id} | status={$o->status} | payment={$o->payment_status} | total_cents={$o->total_cents}");
        $this->line("Customer: {$o->customer_id}");
        foreach ($o->items as $it) {
            $this->line("  - {$it->sku} x{$it->qty} (line_total_cents={$it->line_total_cents})");
        }

        $rows = DB::table('stock_ledger')
            ->where('order_id',$o->id)
            ->orderBy('id')->get();

        $this->line("Stock ledger rows: ".count($rows));
        foreach ($rows as $r) {
            $this->line("  #{$r->id} sku={$r->sku} reason={$r->reason} delta={$r->delta} corr={$r->correlation_id}");
        }
        return self::SUCCESS;
    }
}
