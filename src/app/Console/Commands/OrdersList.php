<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OrdersList extends Command
{
    protected $signature = 'orders:list {--date=}';
    protected $description = 'List recent orders (optionally filter by created date YYYY-MM-DD)';

    public function handle(): int
    {
        $date = $this->option('date');
        $q = DB::table('orders')
            ->select('id','customer_id','total_cents','refunded_cents','status','payment_status','created_at')
            ->orderBy('id','desc')->limit(50);

        if ($date) $q->whereDate('created_at', $date);

        $rows = $q->get();
        foreach ($rows as $r) {
            $this->line(sprintf(
                '#%d cust=%d total=%d refunded=%d status=%s pay=%s at=%s',
                $r->id, $r->customer_id, $r->total_cents, $r->refunded_cents, $r->status, $r->payment_status, $r->created_at
            ));
        }
        if ($rows->isEmpty()) $this->line('(no orders)');
        return self::SUCCESS;
    }
}
