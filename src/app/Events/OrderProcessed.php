<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $orderId,
        public int $customerId,
        public string $status,          // completed | failed
        public string $paymentStatus,   // paid | failed
        public int $totalCents
    ) {}
}
