<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SimulatePayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $orderId) {}

    public function viaQueue(): string { return 'payments'; }

    public function handle(): void
    {
        $rate = (int) (config('app.sim_payment_success_rate') ?? env('SIM_PAYMENT_SUCCESS_RATE', 80));
        $ok = random_int(1,100) <= max(0,min(100,$rate));

        FinalizeOrRollback::dispatch($this->orderId, $ok)->onQueue('orders');
    }
}
