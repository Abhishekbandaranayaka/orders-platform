<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SimulatePayment;

class DebugDispatchPayment extends Command
{
    protected $signature = 'debug:dispatch-payment {orderId}';
    protected $description = 'Dispatch a SimulatePayment job to the payments queue';

    public function handle(): int
    {
        $id = (int) $this->argument('orderId');
        SimulatePayment::dispatch($id)->onQueue('payments');
        $this->info("Queued SimulatePayment for order {$id}");
        return self::SUCCESS;
    }
}
