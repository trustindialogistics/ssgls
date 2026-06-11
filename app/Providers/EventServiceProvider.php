<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        'eloquent.saved: App\Models\LorryReceipt' => [
            \App\Listeners\RecalculateOnLorryReceiptSaved::class,
        ],
        'eloquent.saved: App\Models\Invoice' => [
            \App\Listeners\RecalculateOnOfficeInvoiceSaved::class,
        ],
        'eloquent.created: App\Models\Payment' => [
            \App\Listeners\RecalculateOnPaymentCreated::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
