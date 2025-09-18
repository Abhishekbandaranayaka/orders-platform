<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
        public int $customerId,
        public string $status,        // completed|failed
        public string $paymentStatus, // paid|failed
        public int $totalCents
    ) {}

    public function build()
    {
        return $this->subject("Order #{$this->orderId} {$this->status}")
            ->markdown('mail.order-status', [
                'orderId'       => $this->orderId,
                'customerId'    => $this->customerId,
                'status'        => $this->status,
                'paymentStatus' => $this->paymentStatus,
                'totalCents'    => $this->totalCents,
            ]);
    }
}
