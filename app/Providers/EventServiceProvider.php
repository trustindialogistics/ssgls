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
        'eloquent.deleted: App\Models\LorryReceipt' => [
            \App\Listeners\RecalculateOnLorryReceiptDeleted::class,
        ],
        'eloquent.saved: App\Models\Invoice' => [
            \App\Listeners\RecalculateOnOfficeInvoiceSaved::class,
        ],
        'eloquent.created: App\Models\Invoice' => [
            \App\Listeners\RecalculateOnLrReceiptCreated::class,
        ],
        'eloquent.deleted: App\Models\Invoice' => [
            \App\Listeners\RecalculateOnLrReceiptDeleted::class,
        ],
        'eloquent.created: App\Models\Payment' => [
            \App\Listeners\RecalculateOnPaymentCreated::class,
        ],
        'eloquent.deleted: App\Models\InvoiceItem' => [
            \App\Listeners\RecalculateOnInvoiceItemDeleted::class,
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
