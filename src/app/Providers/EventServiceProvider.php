<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\OrderProcessed::class => [
            \App\Listeners\UpdateAnalyticsOnOrderProcessed::class,
            \App\Listeners\QueueNotificationOnOrderProcessed::class,
        ],
        \App\Events\OrderProcessed::class => [
            \App\Listeners\UpdateAnalyticsOnOrderProcessed::class,
            \App\Listeners\QueueNotificationOnOrderProcessed::class,
        ],
        \App\Events\RefundProcessed::class => [
            \App\Listeners\QueueNotificationOnRefundProcessed::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}