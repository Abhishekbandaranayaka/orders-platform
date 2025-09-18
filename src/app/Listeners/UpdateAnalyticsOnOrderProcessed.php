<?php

namespace App\Listeners;

use App\Events\OrderProcessed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\Analytics;

class UpdateAnalyticsOnOrderProcessed
{
    public function __construct() {}

    public function handle(OrderProcessed $event): void
    {
        $analytics = new Analytics();

        // Idempotency guard: token per order finalization
        $token = "analytics:order:{$event->orderId}:{$event->status}";
        if (!$analytics->once($token)) return;

        if ($event->status === 'completed' && $event->paymentStatus === 'paid') {
            $analytics->incOrderCount(1);
            $analytics->incRevenueCents($event->totalCents);
            $analytics->recomputeAov();
            $analytics->incrCustomerSpend($event->customerId, $event->totalCents);
        }
        // Nothing to do for failed orders in KPIs
    }
}
