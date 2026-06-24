<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportInvoiceRow extends Model
{
    protected $guarded = ['id'];

    protected $dates = [
        'created_at',
        'updated_at',
        'old_bill_date',
    ];

    public function transportInvoice(): BelongsTo
    {
        return $this->belongsTo(TransportInvoice::class);
    }
}

