<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Order;

class DebugResetOrder extends Command
{
    protected $signature = 'debug:reset-order {orderId} {--keep-ledger}';
    protected $description = 'Reset an order to imported/pending; clears ledger unless --keep-ledger';

    public function handle(): int
    {
        $id = (int)$this->argument('orderId');
        $keep = (bool)$this->option('keep-ledger');

        $o = Order::with('items')->find($id);
        if (!$o) { $this->error("Order {$id} not found"); return self::FAILURE; }

        DB::transaction(function () use ($o, $keep) {
            // Optional: clear ledger rows for this order
            if (!$keep) {
                DB::table('stock_ledger')->where('order_id', $o->id)->delete();
            }
            // Reset main fields
            $o->update([
                'status' => 'imported',
                'payment_status' => 'pending',
                // Keep totals & items as-is
            ]);
        });

        $this->info("Order {$o->id} reset to imported/pending".($keep ? " (ledger kept)" : " (ledger cleared)"));
        return self::SUCCESS;
    }
}
