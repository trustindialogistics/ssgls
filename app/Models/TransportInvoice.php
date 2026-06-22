<?php

namespace App\Models;

use App\Facades\Hashids;
use App\Facades\PDF;
use App\Traits\GeneratesPdfTrait;
use App\Models\CompanySetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportInvoice extends Model
{
    use GeneratesPdfTrait;

    protected $guarded = ['id'];

    protected $dates = [
        'created_at',
        'updated_at',
        'invoice_date',
        'due_date',
    ];

    protected $appends = [
        'formattedInvoiceDate',
        'formattedDueDate',
        'transportInvoicePdfUrl',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->creator_id = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(TransportInvoiceRow::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getTransportInvoicePdfUrlAttribute()
    {
        return url('/transport-invoices/pdf/'.$this->unique_hash);
    }

    public function getFormattedInvoiceDateAttribute()
    {
        if (! $this->invoice_date) {
            return '';
        }

        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id) ?: 'd M Y';

        return Carbon::parse($this->invoice_date)->format($dateFormat);
    }

    public function getFormattedDueDateAttribute()
    {
        if (! $this->due_date) {
            return '';
        }

        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id) ?: 'd M Y';

        return Carbon::parse($this->due_date)->format($dateFormat);
    }

    public function getCompanyAddress()
    {
        if ($this->company && (! $this->company->address()->exists())) {
            return '';
        }

        $format = CompanySetting::getSetting('invoice_company_address_format', $this->company_id);

        return $format ? $this->getFormattedString($format) : '';
    }

    public function getCustomerBillingAddress()
    {
        if ($this->customer && (! $this->customer->billingAddress()->exists())) {
            return '';
        }

        $format = CompanySetting::getSetting('invoice_billing_address_format', $this->company_id);

        return $format ? $this->getFormattedString($format) : '';
    }

    public function getExtraFields()
    {
        return [
            '{INVOICE_DATE}' => $this->formattedInvoiceDate,
            '{INVOICE_DUE_DATE}' => $this->formattedDueDate,
            '{INVOICE_NUMBER}' => (string) ($this->lr_number ?? ''),
        ];
    }

    public static function createTransportInvoice(array $payload): self
    {
        $invoice = self::create([
            'company_id' => $payload['company_id'] ?? null,
            'customer_id' => $payload['customer_id'] ?? null,
            'lr_number' => $payload['lr_number'] ?? null,
            'branch_code' => $payload['branch_code'] ?? null,
            'invoice_date' => $payload['invoice_date'] ?? null,
            'due_date' => $payload['due_date'] ?? null,
        ]);

        $invoice->unique_hash = Hashids::connection(self::class)->encode($invoice->id);
        $invoice->save();

        $invoice->syncRows($payload['rows'] ?? []);

        return $invoice->fresh(['rows', 'customer', 'company']);
    }

    public function updateTransportInvoice(array $payload): self
    {
        $this->update([
            'customer_id' => $payload['customer_id'] ?? $this->customer_id,
            'lr_number' => $payload['lr_number'] ?? $this->lr_number,
            'branch_code' => $payload['branch_code'] ?? $this->branch_code,
            'invoice_date' => $payload['invoice_date'] ?? $this->invoice_date,
            'due_date' => $payload['due_date'] ?? $this->due_date,
        ]);

        $this->syncRows($payload['rows'] ?? []);

        return $this->fresh(['rows', 'customer', 'company']);
    }

    public function syncRows(array $rows): void
    {
        $this->rows()->delete();

        foreach ($rows as $row) {
            $this->rows()->create([
                'consignment_no' => $row['consignment_no'] ?? null,
                'old_bill_date' => $row['old_bill_date'] ?? null,
                'invoice_no' => $row['invoice_no'] ?? null,
                'destination' => $row['destination'] ?? null,
                'vehicle_no' => $row['vehicle_no'] ?? null,
                'pkg' => $row['pkg'] ?? null,
                'charged_weight' => $row['charged_weight'] ?? null,
                'rate' => $row['rate'] ?? null,
                'other_charge' => $row['other_charge'] ?? null,
                'dd_charge' => $row['dd_charge'] ?? null,
                'amount' => $row['amount'] ?? null,
            ]);
        }
    }

    public function getPDFData()
    {
        $company = Company::find($this->company_id);
        $logo = $company?->logo_path;

        view()->share([
            'transportInvoice' => $this->loadMissing(['rows', 'customer', 'company']),
            'company' => $company,
            'logo' => $logo ?? null,
            'company_address' => $this->getCompanyAddress(),
            'billing_address' => $this->getCustomerBillingAddress(),
        ]);

        $templatePath = 'pdf_templates::transport-invoice.ssgl_transport';

        if (request()->has('preview')) {
            return view($templatePath);
        }

        return PDF::loadView($templatePath);
    }
}
