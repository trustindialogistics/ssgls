<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LorryPartyProfile extends Model
{
    public const TYPE_OWNER = 'OWNER';

    public const TYPE_DRIVER = 'DRIVER';

    public const TYPE_BROKER = 'BROKER';

    protected $guarded = ['id'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
