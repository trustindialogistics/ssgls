<?php

namespace App\Models;

use App;
use App\Facades\Hashids;
use App\Facades\PDF;
use App\Mail\SendInvoiceMail;
use App\Models\LorryReceipt;
use App\Models\LorryPartyProfile;
use App\Services\SerialNumberFormatter;
use App\Space\PdfTemplateUtils;
use App\Traits\GeneratesPdfTrait;
use App\Traits\HasCustomFieldsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nwidart\Modules\Facades\Module;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Invoice extends Model implements HasMedia
{
    use GeneratesPdfTrait;
    use HasCustomFieldsTrait;
    use HasFactory;
    use InteractsWithMedia;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_SENT = 'SENT';

    public const STATUS_VIEWED = 'VIEWED';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_UNPAID = 'UNPAID';

    public const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';

    public const STATUS_PAID = 'PAID';

    public const TEMPLATE_OFFICE_INVOICE = 'office_invoice';

    public const TEMPLATE_LR_RECEIPT = 'lr_receipt';

    public const TEMPLATE_LORRY_RECEIPT = 'lorry_receipt';

    public const LORRY_DOCUMENT_COLLECTIONS = [
        'aadhar_front_copy' => 'Aadhar Front Copy',
        'aadhar_back_copy' => 'Aadhar Back Copy',
        'pan_card_front_copy' => 'Pan Card Copy Front',
        'pan_card_back_copy' => 'Pan Card Copy Back',
        'rc_copy_front' => 'RC Copy Front',
        'rc_copy_back' => 'RC Copy Back',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'invoice_date',
        'due_date',
    ];

    protected $guarded = [
        'id',
    ];

    protected $appends = [
        'formattedCreatedAt',
        'formattedInvoiceDate',
        'formattedDueDate',
        'formattedDueAmount',
        'invoicePdfUrl',
        'amountPaid',
        'lorryReceiptAdvanceAmount',
        'lorryReceiptDisplayNetAmount',
        'formattedAdvanceOn',
        'formattedFinalBalanceOn',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'tax' => 'integer',
            'sub_total' => 'integer',
            'discount' => 'float',
            'discount_val' => 'integer',
            'exchange_rate' => 'float',
            'modified_dates' => 'array',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->date_created = now();
        });

        static::updating(function ($model) {
            $model->date_modified = now();
            $dates = $model->modified_dates ?? [];
            $dates[] = now()->toDateTimeString();
            $model->modified_dates = $dates;
        });

        static::saving(function ($model) {
            if ($model->template_name === self::TEMPLATE_LORRY_RECEIPT) {
                $model->determineLorryReceiptStatus();
            }
        });

        static::deleting(function (Invoice $invoice) {
            if ($invoice->template_name === self::TEMPLATE_LORRY_RECEIPT && ! empty($invoice->invoice_number)) {
                LorryReceipt::query()
                    ->where('company_id', $invoice->company_id)
                    ->where(function ($query) use ($invoice) {
                        $query->where('challan_no', $invoice->invoice_number)
                            ->orWhere('contract_no', $invoice->invoice_number);
                    })
                    ->delete();
            }
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function emailLogs(): MorphMany
    {
        return $this->morphMany('App\Models\EmailLog', 'mailable');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function consigneeCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'consignee_customer_id');
    }

    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function getInvoicePdfUrlAttribute()
    {
        return url('/invoices/pdf/'.$this->unique_hash);
    }

    public function getPaymentModuleEnabledAttribute()
    {
        if (Module::has('Payments')) {
            return Module::isEnabled('Payments');
        }

        return false;
    }



    public function getAmountPaidAttribute(): int|float
    {
        return $this->payments()
            ->sum('amount');
    }

    public function getLorryReceiptAdvanceAmountAttribute(): int|float
    {
        if ($this->template_name !== self::TEMPLATE_LORRY_RECEIPT) {
            return 0;
        }

        $lorryReceipt = $this->matchingLorryReceipt();

        if ($lorryReceipt) {
            return self::numericTransportAmount($lorryReceipt->advance_amount);
        }

        return $this->customFieldAmount(['Advance Paid Rs', 'Advance Amount']) ?? 0;
    }

    public function getLorryReceiptDisplayNetAmountAttribute(): int|float|null
    {
        if ($this->template_name !== self::TEMPLATE_LORRY_RECEIPT) {
            return null;
        }

        $lorryReceipt = $this->matchingLorryReceipt();

        if ($lorryReceipt) {
            if (self::lorryReceiptHasFinalPaymentOperation($lorryReceipt)) {
                return self::numericTransportAmount($lorryReceipt->net_amount_payable);
            }

            return $this->lorryReceiptSectionCBalance($lorryReceipt);
        }

        if ($this->customFieldsHaveFinalPaymentOperation()) {
            return $this->customFieldFinalNetAmountPayable();
        }

        return $this->customFieldSectionCBalance();
    }

    public function getAllowEditAttribute()
    {
        $retrospective_edit = CompanySetting::getSetting('retrospective_edits', $this->company_id);

        $allowed = true;

        $status = [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
            self::STATUS_VIEWED,
            self::STATUS_COMPLETED,
        ];

        if ($retrospective_edit == 'disable_on_invoice_sent' && (in_array($this->status, $status)) && ($this->paid_status === Invoice::STATUS_PARTIALLY_PAID || $this->paid_status === Invoice::STATUS_PAID)) {
            $allowed = false;
        } elseif ($retrospective_edit == 'disable_on_invoice_partial_paid' && ($this->paid_status === Invoice::STATUS_PARTIALLY_PAID || $this->paid_status === Invoice::STATUS_PAID)) {
            $allowed = false;
        } elseif ($retrospective_edit == 'disable_on_invoice_paid' && $this->paid_status === Invoice::STATUS_PAID) {
            $allowed = false;
        }

        return $allowed;
    }

    public function getPreviousStatus()
    {
        if ($this->viewed) {
            return self::STATUS_VIEWED;
        } elseif ($this->sent) {
            return self::STATUS_SENT;
        } else {
            return self::STATUS_DRAFT;
        }
    }

    public function getFormattedNotesAttribute($value)
    {
        return $this->getNotes();
    }

    public function getFormattedCreatedAtAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->created_at)->format($dateFormat);
    }

    public function getFormattedDueDateAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->due_date)->translatedFormat($dateFormat);
    }

    public function getFormattedDueAmountAttribute($value)
    {
        $currency = $this->currency;

        if (! $currency) {
            $currency = Currency::findOrFail(CompanySetting::getSetting('currency', $this->company_id));
        }

        return format_money_pdf($this->due_amount, $currency);
    }

    public function getFormattedInvoiceDateAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);
        $timeFormat = CompanySetting::getSetting('carbon_time_format', $this->company_id);
        $invoiceTimeEnabled = CompanySetting::getSetting('invoice_use_time', $this->company_id);

        if ($invoiceTimeEnabled === 'YES') {
            $dateFormat .= ' '.$timeFormat;
        }

        return Carbon::parse($this->invoice_date)->translatedFormat($dateFormat);
    }

    public function scopeWhereStatus($query, $status)
    {
        return $query->where('invoices.status', $status);
    }

    public function scopeWherePaidStatus($query, $status)
    {
        return $query->where('invoices.paid_status', $status);
    }

    public function scopeWhereDueStatus($query, $status)
    {
        return $query->whereIn('invoices.paid_status', [
            self::STATUS_UNPAID,
            self::STATUS_PARTIALLY_PAID,
        ]);
    }

    public function scopeWhereRegularInvoice($query)
    {
        return $query->where('invoices.template_name', self::TEMPLATE_OFFICE_INVOICE);
    }

    public function scopeWhereInvoiceNumber($query, $invoiceNumber)
    {
        return $query->where('invoices.invoice_number', 'LIKE', '%'.$invoiceNumber.'%');
    }

    public function scopeInvoicesBetween($query, $start, $end)
    {
        return $query->whereBetween(
            'invoices.invoice_date',
            [$start->format('Y-m-d'), $end->format('Y-m-d')]
        );
    }

    public function scopeWhereSearch($query, $search)
    {
        foreach (explode(' ', $search) as $term) {
            $query->whereHas('customer', function ($query) use ($term) {
                $query->where('name', 'LIKE', '%'.$term.'%')
                    ->orWhere('contact_name', 'LIKE', '%'.$term.'%')
                    ->orWhere('company_name', 'LIKE', '%'.$term.'%');
            });
        }
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy)
    {
        $query->orderBy($orderByField, $orderBy);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters)->filter()->all();

        return $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->whereSearch($search);
        })->when($filters['status'] ?? null, function ($query, $status) {
            match ($status) {
                self::STATUS_UNPAID, self::STATUS_PARTIALLY_PAID, self::STATUS_PAID => $query->wherePaidStatus($status),
                'DUE' => $query->whereDueStatus($status),
                default => $query->whereStatus($status),
            };
        })->when($filters['paid_status'] ?? null, function ($query, $paidStatus) {
            $query->wherePaidStatus($paidStatus);
        })->when($filters['invoice_id'] ?? null, function ($query, $invoiceId) {
            $query->whereInvoice($invoiceId);
        })->when($filters['invoice_number'] ?? null, function ($query, $invoiceNumber) {
            $query->whereInvoiceNumber($invoiceNumber);
        })->when($filters['template_name'] ?? null, function ($query, $templateName) {
            $query->where('template_name', $templateName);
        })->when(($filters['from_date'] ?? null) && ($filters['to_date'] ?? null), function ($query) use ($filters) {
            $start = Carbon::parse($filters['from_date']);
            $end = Carbon::parse($filters['to_date']);
            $query->invoicesBetween($start, $end);
        })->when($filters['customer_id'] ?? null, function ($query, $customerId) {
            $query->where('customer_id', $customerId);
        })->when($filters['orderByField'] ?? null, function ($query, $orderByField) use ($filters) {
            $orderBy = $filters['orderBy'] ?? 'desc';
            $query->orderBy($orderByField, $orderBy);
        }, function ($query) {
            $query->orderBy('sequence_number', 'desc');
        });
    }

    public function scopeWhereInvoice($query, $invoice_id)
    {
        $query->orWhere('id', $invoice_id);
    }

    public function scopeWhereCompany($query)
    {
        $query->where('invoices.company_id', request()->header('company'));
    }

    public function scopeWhereCompanyId($query, $company)
    {
        $query->where('invoices.company_id', $company);
    }

    public function scopeWhereCustomer($query, $customer_id)
    {
        $query->where(function ($q) use ($customer_id) {
            $q->where('invoices.customer_id', $customer_id)
              ->orWhere('invoices.consignee_customer_id', $customer_id);
        });
    }

    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    public static function createInvoice($request)
    {
        $data = $request->getInvoicePayload();

        if ($request->has('invoiceSend')) {
            $data['status'] = Invoice::STATUS_SENT;
        }

        $invoice = Invoice::create($data);

        $serial = (new SerialNumberFormatter)
            ->setModel($invoice)
            ->setCompany($invoice->company_id)
            ->setCustomer($invoice->customer_id)
            ->setNextNumbers();

        $invoice->sequence_number = $serial->nextSequenceNumber;
        $invoice->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $invoice->unique_hash = Hashids::connection(Invoice::class)->encode($invoice->id);
        $invoice->save();

        self::createItems($invoice, $request->items);

        $invoice->syncLorryDocuments($request->input('lorry_documents', []));

        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));

        if ((string) $data['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($invoice);
        }

        if ($request->has('taxes') && (! empty($request->taxes))) {
            self::createTaxes($invoice, $request->taxes);
        }

        if ($request->customFields) {
            $invoice->addCustomFields($request->customFields);
        }

        $invoice = Invoice::with([
            'items',
            'items.fields',
            'items.fields.customField',
            'customer',
            'taxes',
        ])
            ->find($invoice->id);

        $invoice->syncLorryReceiptRecord();

        return $invoice;
    }

    public function updateInvoice($request)
    {
        $serial = (new SerialNumberFormatter)
            ->setModel($this)
            ->setCompany($this->company_id)
            ->setCustomer($request->customer_id)
            ->setModelObject($this->id)
            ->setNextNumbers();

        $data = $request->getInvoicePayload();
        $oldTotal = $this->total;
        $newTotal = (float) $data['total'];

        $total_paid_amount = $this->total - $this->due_amount;

        if ($total_paid_amount > 0 && $this->customer_id !== $request->customer_id) {
            return 'customer_cannot_be_changed_after_payment_is_added';
        }

        if ($newTotal >= 0 && $newTotal < $total_paid_amount) {
            return 'total_invoice_amount_must_be_more_than_paid_amount';
        }

        if ((float) $oldTotal !== $newTotal) {
            $oldTotal = (int) round($newTotal) - (int) $oldTotal;
        } else {
            $oldTotal = 0;
        }

        $data['due_amount'] = ($this->due_amount + $oldTotal);
        $data['base_due_amount'] = $data['due_amount'] * $data['exchange_rate'];
        $data['customer_sequence_number'] = $serial->nextCustomerSequenceNumber;

        $this->update($data);

        $statusData = $this->getInvoiceStatusByAmount($data['due_amount']);
        if (! empty($statusData)) {
            $this->update($statusData);
        }

        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));

        if ((string) $data['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($this);
        }

        $this->items->map(function ($item) {
            $fields = $item->fields()->get();

            $fields->map(function ($field) {
                $field->delete();
            });
        });

        $this->items()->delete();
        $this->taxes()->delete();

        self::createItems($this, $request->items);

        $this->syncLorryDocuments($request->input('lorry_documents', []));

        if ($request->has('taxes') && (! empty($request->taxes))) {
            self::createTaxes($this, $request->taxes);
        }

        if ($request->customFields) {
            $this->updateCustomFields($request->customFields);
        }

        $invoice = Invoice::with([
            'items',
            'items.fields',
            'items.fields.customField',
            'customer',
            'taxes',
        ])
            ->find($this->id);

        $invoice->syncLorryReceiptRecord();

        return $invoice;
    }

    public function sendInvoiceData($data)
    {
        $data['invoice'] = $this->toArray();
        $data['customer'] = $this->customer->toArray();
        $data['company'] = Company::find($this->company_id);

        if ($this->template_name === self::TEMPLATE_LR_RECEIPT) {
            $data['subject'] = $this->getLrEmailString($data['subject']);
            $data['body'] = $this->getLrEmailString($data['body']);
        }

        $data['subject'] = $this->getEmailString($data['subject']);
        $data['body'] = $this->getEmailString($data['body']);
        $data['attach']['copy_type'] = $data['copy_type'] ?? null;
        $data['attach']['data'] = $this->getPDFData($data['attach']['copy_type']);

        return $data;
    }

    public function preview($data)
    {
        $data = $this->sendInvoiceData($data);

        return [
            'type' => 'preview',
            'view' => new SendInvoiceMail($data),
        ];
    }

    public function send($data)
    {
        $data = $this->sendInvoiceData($data);

        $mail = \Mail::to($data['to']);
        if (! empty($data['cc'])) {
            $mail->cc($data['cc']);
        }
        if (! empty($data['bcc'])) {
            $mail->bcc($data['bcc']);
        }
        $mail->send(new SendInvoiceMail($data));

        if ($this->status == Invoice::STATUS_DRAFT) {
            $this->status = Invoice::STATUS_SENT;
            $this->sent = true;
            $this->save();
        }

        return [
            'success' => true,
            'type' => 'send',
        ];
    }

    public static function createItems($invoice, $invoiceItems)
    {
        $exchange_rate = $invoice->exchange_rate;

        foreach ($invoiceItems as $invoiceItem) {
            $invoiceItem['company_id'] = $invoice->company_id;
            $invoiceItem['exchange_rate'] = $exchange_rate;
            $invoiceItem['base_price'] = $invoiceItem['price'] * $exchange_rate;
            $invoiceItem['base_discount_val'] = $invoiceItem['discount_val'] * $exchange_rate;
            $invoiceItem['base_tax'] = $invoiceItem['tax'] * $exchange_rate;
            $invoiceItem['base_total'] = $invoiceItem['total'] * $exchange_rate;

            if (array_key_exists('recurring_invoice_id', $invoiceItem)) {
                unset($invoiceItem['recurring_invoice_id']);
            }

            $item = $invoice->items()->create($invoiceItem);

            if (array_key_exists('taxes', $invoiceItem) && $invoiceItem['taxes']) {
                foreach ($invoiceItem['taxes'] as $tax) {
                    $tax['company_id'] = $invoice->company_id;
                    $tax['exchange_rate'] = $invoice->exchange_rate;
                    $tax['base_amount'] = $tax['amount'] * $exchange_rate;
                    $tax['currency_id'] = $invoice->currency_id;

                    if (gettype($tax['amount']) !== 'NULL') {
                        if (array_key_exists('recurring_invoice_id', $invoiceItem)) {
                            unset($invoiceItem['recurring_invoice_id']);
                        }

                        $item->taxes()->create($tax);
                    }
                }
            }

            if (array_key_exists('custom_fields', $invoiceItem) && $invoiceItem['custom_fields']) {
                $item->addCustomFields($invoiceItem['custom_fields']);
            }
        }
    }

    public static function createTaxes($invoice, $taxes)
    {

        $exchange_rate = $invoice->exchange_rate;

        foreach ($taxes as $tax) {
            $tax['company_id'] = $invoice->company_id;
            $tax['exchange_rate'] = $invoice->exchange_rate;
            $tax['base_amount'] = $tax['amount'] * $exchange_rate;
            $tax['currency_id'] = $invoice->currency_id;

            if (gettype($tax['amount']) !== 'NULL') {
                if (array_key_exists('recurring_invoice_id', $tax)) {
                    unset($tax['recurring_invoice_id']);
                }

                $invoice->taxes()->create($tax);
            }
        }
    }

    public function getPDFData(?string $copyType = null, bool $includeDocuments = false)
    {
        $taxes = collect();

        if ($this->tax_per_item === 'YES') {
            foreach ($this->items as $item) {
                foreach ($item->taxes as $tax) {
                    $found = $taxes->filter(function ($item) use ($tax) {
                        return $item->tax_type_id == $tax->tax_type_id;
                    })->first();

                    if ($found) {
                        $found->amount += $tax->amount;
                    } else {
                        $taxes->push($tax);
                    }
                }
            }
        }

        $allowedInvoiceTemplates = [self::TEMPLATE_OFFICE_INVOICE, self::TEMPLATE_LR_RECEIPT, self::TEMPLATE_LORRY_RECEIPT];
        $invoiceTemplate = self::find($this->id)->template_name;

        if (! in_array($invoiceTemplate, $allowedInvoiceTemplates, true)) {
            $invoiceTemplate = self::TEMPLATE_OFFICE_INVOICE;
        }

        $company = Company::find($this->company_id);
        $locale = CompanySetting::getSetting('language', $company->id);
        $customFields = CustomField::where('model_type', 'Item')->get();

        App::setLocale($locale);

        $logo = $company->logo_path;
        $copyLabels = [
            'consignee' => 'CONSIGNEE COPY',
            'driver' => 'DRIVER COPY',
            'consignor' => 'CONSIGNOR COPY',
            'ho' => 'H. O. COPY',
            'file' => 'FILE COPY',
        ];
        $copyLabel = $copyLabels[$copyType ?: request()->query('copy')] ?? '';

        $includeDocs = $includeDocuments || request()->has('include_documents') || request()->has('documents');

        view()->share([
            'invoice' => $this,
            'lorryDocumentCollections' => $includeDocs ? self::LORRY_DOCUMENT_COLLECTIONS : [],
            'customFields' => $customFields,
            'company_address' => $this->getCompanyAddress(),
            'shipping_address' => $this->getCustomerShippingAddress(),
            'billing_address' => $this->getCustomerBillingAddress(),
            'notes' => $this->getNotes(),
            'logo' => $logo ?? null,
            'taxes' => $taxes,
            'copyLabel' => $copyLabel,
        ]);

        $template = PdfTemplateUtils::findFormattedTemplate('invoice', $invoiceTemplate, '');
        $templatePath = $template['custom'] ? sprintf('pdf_templates::invoice.%s', $invoiceTemplate) : sprintf('app.pdf.invoice.%s', $invoiceTemplate);

        if (request()->has('preview')) {
            return view($templatePath);
        }

        $pdf = PDF::loadView($templatePath);

        if ($invoiceTemplate === self::TEMPLATE_LORRY_RECEIPT) {
            $pdf->setPaper('a4', 'portrait');
        } elseif (in_array($invoiceTemplate, $allowedInvoiceTemplates, true)) {
            $pdf->setPaper('a4', 'landscape');
        }

        return $pdf;
    }

    public function getEmailAttachmentSetting()
    {
        return true;
    }

    public function getCompanyAddress()
    {
        if ($this->company && (! $this->company->address()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('invoice_company_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    public function getCustomerShippingAddress()
    {
        if ($this->customer && (! $this->customer->shippingAddress()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('invoice_shipping_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    public function getCustomerBillingAddress()
    {
        if ($this->customer && (! $this->customer->billingAddress()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('invoice_billing_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    public function getNotes()
    {
        return $this->getFormattedString($this->notes);
    }

    public function getEmailString($body)
    {
        $values = array_merge($this->getFieldsArray(), $this->getExtraFields());

        $body = strtr($body, $values);

        return preg_replace('/{(.*?)}/', '', $body);
    }

    public function getLrEmailString($body)
    {
        return str_ireplace(['new invoice', 'invoice'], ['new LR', 'LR'], $body);
    }

    public function getExtraFields()
    {
        return [
            '{INVOICE_DATE}' => $this->formattedInvoiceDate,
            '{INVOICE_DUE_DATE}' => $this->formattedDueDate,
            '{INVOICE_NUMBER}' => $this->invoice_number,
            '{INVOICE_REF_NUMBER}' => $this->reference_number,
        ];
    }

    public function addInvoicePayment($amount)
    {
        $this->due_amount += $amount;
        if ($this->due_amount > $this->total) {
            $this->due_amount = $this->total;
        }
        $this->base_due_amount = $this->due_amount * $this->exchange_rate;

        $this->changeInvoiceStatus($this->due_amount);
    }

    public function subtractInvoicePayment($amount)
    {
        $this->due_amount -= $amount;
        if ($this->due_amount < 0) {
            $this->due_amount = 0;
        }
        $this->base_due_amount = $this->due_amount * $this->exchange_rate;

        $this->changeInvoiceStatus($this->due_amount);
    }

    /**
     * Set the invoice status from amount.
     *
     * @return array
     */
    public function getInvoiceStatusByAmount($amount)
    {
        if ($this->template_name === self::TEMPLATE_LORRY_RECEIPT) {
            return [];
        }

        if ($amount < 0) {
            return [];
        }

        if ($amount == 0) {
            $data = [
                'status' => Invoice::STATUS_COMPLETED,
                'paid_status' => Invoice::STATUS_PAID,
                'overdue' => false,
            ];
        } elseif ($amount == $this->total) {
            $data = [
                'status' => $this->getPreviousStatus(),
                'paid_status' => Invoice::STATUS_UNPAID,
            ];
        } else {
            $data = [
                'status' => $this->getPreviousStatus(),
                'paid_status' => Invoice::STATUS_PARTIALLY_PAID,
            ];
        }

        return $data;
    }

    /**
     * Changes the invoice status right away
     *
     * @return string[]|void
     */
    public function changeInvoiceStatus($amount)
    {
        $status = $this->getInvoiceStatusByAmount($amount);
        if (! empty($status)) {
            foreach ($status as $key => $value) {
                $this->setAttribute($key, $value);
            }
            $this->save();
        }
    }

    public static function deleteInvoices($ids)
    {
        foreach ($ids as $id) {
            $invoice = self::find($id);

            if (! $invoice) {
                continue;
            }

            if ($invoice->transactions()->exists()) {
                $invoice->transactions()->delete();
            }

            $invoice->clearMediaCollection('pod');

            foreach (self::LORRY_DOCUMENT_COLLECTIONS as $collection => $label) {
                $invoice->clearMediaCollection($collection);
            }

            $invoice->delete();
        }

        return true;
    }

    public function syncLorryDocuments(?array $documents): void
    {
        if ($this->template_name !== self::TEMPLATE_LORRY_RECEIPT || empty($documents)) {
            return;
        }

        foreach (self::LORRY_DOCUMENT_COLLECTIONS as $collection => $label) {
            $document = $documents[$collection] ?? null;

            if (! is_array($document) || empty($document['data']) || empty($document['name'])) {
                continue;
            }

            $this->clearMediaCollection($collection);

            $this->addMediaFromBase64($this->sanitizeBase64Pdf($document['data'], $document['name']))
                ->usingFileName($document['name'])
                ->toMediaCollection($collection);
        }
    }

    public function syncLorryReceiptRecord(): void
    {
        if ($this->template_name !== self::TEMPLATE_LORRY_RECEIPT) {
            return;
        }

        $challanNo = $this->invoice_number;

        if (empty($challanNo)) {
            return;
        }

        $lorryReceipt = LorryReceipt::query()
            ->where('company_id', $this->company_id)
            ->where(function ($query) use ($challanNo) {
                $query->where('challan_no', $challanNo)
                    ->orWhere('contract_no', $challanNo);
            })
            ->first() ?? new LorryReceipt();

        $lorryReceipt->company_id = $this->company_id;
        $lorryReceipt->challan_no = $challanNo;

        // Custom fields mappings
        $fieldMap = [
            'From' => 'from_name',
            'To' => 'to_name',
            'No Of Pages' => 'no_of_pages',
            'No Of Packages' => 'no_of_pkgs',
            'Actual Weight' => 'actual_weight',
            'Charge Weight' => 'charge_weight',
            'Lorry No' => 'lorry_no',
            'Regd at' => 'regd_at',
            'Body Type' => 'body_type',
            'Make' => 'make',
            'Model' => 'vehicle_model',
            'Colour' => 'colour',
            'Chasis No' => 'chasis_no',
            'Engine No' => 'engine_no',
            'Owner Name' => 'owner_name',
            'Owner Address' => 'owner_address',
            'Owner Phone No' => 'owner_phone',
            'Owner PAN No' => 'financer_name',
            'Financer Address' => 'financer_address',
            'Driver Name' => 'driver_name',
            'Driver Address' => 'driver_address',
            'Driver Place' => 'driver_place',
            'Driver Licence No' => 'driver_licence_no',
            'Driver Licence Date' => 'driver_licence_date',
            'Driver Licence Issued By' => 'driver_licence_issued_by',
            'Driver RTO' => 'driver_rto_address',
            'Driver Valid Up To' => 'driver_valid_up_to',
            'Broker Name' => 'broker_name',
            'Broker Address' => 'broker_address',
            'Broker Pan No' => 'advice_no',
            'Advice Date' => 'advice_date',
            'Destination Broker Name' => 'destination_broker_name',
            'Destination Broker Address' => 'destination_broker_address',
            'Broker Phone No' => 'broker_phone',
            'Paid To' => 'paid_to',
            'Lorry Hire' => 'lorry_hire_amount',
            'Add Other Charges' => 'other_charges_amount',
            'Gross Hire Rupees' => 'gross_hire_rupees',
            'Advance Paid by Cash/Cheque No' => 'advance_cash_cheque_no',
            'Advance On' => 'advance_on',
            'Bank' => 'advance_bank',
            'Advance Paid Rs' => 'advance_amount',
            'Balance Payable at' => 'balance_payable_at',
            'Balance Amount' => 'balance_amount',
            'Balance Rupees Only' => 'balance_rupees_only',
            'Hire Passed By' => 'hire_passed_by',
            'Hire Certified By' => 'hire_certified_by',
            'Hire Prepared By' => 'hire_prepared_by',
            'Advance Received By' => 'advance_received_by',
            'Loaded By' => 'loaded_by',
            'Final Paid To' => 'final_paid_to',
            'Add Detention Rs.' => 'detention_amount',
            'Extra Hire Rs' => 'extra_hire_amount',
            'Other Rs' => 'final_other_amount',
            'Final Total Extra Amount' => 'final_total_extra_amount',
            'Grand Total' => 'grand_total_amount',
            'Less Adv. at other branch' => 'less_advance_other_branch_amount',
            'Less Deduction for Claims' => 'less_deduction_claims_amount',
            'Total Less Amount' => 'total_less_amount',
            'Final Balance Amount Paid at' => 'final_balance_paid_at',
            'Final Balance Code' => 'final_balance_code',
            'Final Balance Date' => 'final_balance_on',
            'Net Amount Payable' => 'net_amount_payable',
            'Cash/Cheque No.' => 'final_cash_cheque_no',
            'Final Cash Cheque On' => 'final_cash_cheque_on',
            'Final Bank' => 'final_bank',
            'Final Rupees Only' => 'final_rupees_only',
            'Final Passed By' => 'final_passed_by',
            'Final Certified By' => 'final_certified_by',
            'Final Prepared By' => 'final_prepared_by',
            'Final Payment Received By' => 'final_payment_received_by',
            'Received No Of Bilties' => 'received_no_bilties',
            'Owner Bank Account No' => 'owner_bank_account_no',
            'Driver Bank Account No' => 'driver_bank_account_no',
            'Broker Bank Account No' => 'broker_bank_account_no',
        ];

        foreach ($fieldMap as $label => $attribute) {
            $value = $this->getCustomFieldValueByLabel($label);
            $lorryReceipt->{$attribute} = $value;
        }

        // Also resolve the customer IDs
        $ownerName = $lorryReceipt->owner_name;
        if (! empty($ownerName)) {
            $ownerProfile = LorryPartyProfile::query()
                ->where('company_id', $this->company_id)
                ->where('type', LorryPartyProfile::TYPE_OWNER)
                ->where('name', $ownerName)
                ->first();
            $lorryReceipt->owner_customer_id = $ownerProfile?->customer_id;
        }

        $driverName = $lorryReceipt->driver_name;
        if (! empty($driverName)) {
            $driverProfile = LorryPartyProfile::query()
                ->where('company_id', $this->company_id)
                ->where('type', LorryPartyProfile::TYPE_DRIVER)
                ->where('name', $driverName)
                ->first();
            $lorryReceipt->driver_customer_id = $driverProfile?->customer_id;
        }

        $brokerName = $lorryReceipt->broker_name;
        if (! empty($brokerName)) {
            $brokerProfile = LorryPartyProfile::query()
                ->where('company_id', $this->company_id)
                ->where('type', LorryPartyProfile::TYPE_BROKER)
                ->where('name', $brokerName)
                ->first();
            $lorryReceipt->broker_customer_id = $brokerProfile?->customer_id;
        }

        $attributes = [];
        foreach (LorryReceipt::PAYLOAD_FIELDS as $field) {
            $attributes[$field] = $lorryReceipt->{$field};
        }

        $attributes['company_id'] = $this->company_id;

        if ($lorryReceipt->exists) {
            $lorryReceipt->updateFromPayload($attributes);
        } else {
            LorryReceipt::createFromPayload($attributes);
        }
    }

    public function getCustomFieldValueByLabel(string $label): mixed
    {
        $value = $this->fields()
            ->with('customField')
            ->whereHas('customField', function ($query) use ($label) {
                $query->where('label', $label)
                    ->orWhere('name', $label);
            })->first();

        return $value?->defaultAnswer;
    }

    private function sanitizeBase64Pdf(string $data, string $fileName): string
    {
        if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'pdf') {
            return $data;
        }

        $parts = explode(',', $data, 2);

        if (count($parts) !== 2) {
            return $data;
        }

        $decoded = base64_decode($parts[1], true);

        if ($decoded === false) {
            return $data;
        }

        $pdfOffset = strpos($decoded, '%PDF');

        if ($pdfOffset === false || $pdfOffset === 0) {
            return $data;
        }

        return $parts[0].','.base64_encode(substr($decoded, $pdfOffset));
    }

    private ?LorryReceipt $cachedLorryReceipt = null;

    private bool $lorryReceiptLoaded = false;

    private function matchingLorryReceipt(): ?LorryReceipt
    {
        if ($this->lorryReceiptLoaded) {
            return $this->cachedLorryReceipt;
        }

        $this->lorryReceiptLoaded = true;

        if (empty($this->invoice_number)) {
            return null;
        }

        $this->cachedLorryReceipt = LorryReceipt::query()
            ->when($this->company_id, fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->where(function ($query) {
                $query->where('challan_no', $this->invoice_number)
                    ->orWhere('contract_no', $this->invoice_number);
            })
            ->latest()
            ->first();

        return $this->cachedLorryReceipt;
    }

    public function getFormattedAdvanceOnAttribute(): ?string
    {
        $lorryReceipt = $this->matchingLorryReceipt();
        if (!$lorryReceipt || empty($lorryReceipt->advance_on)) {
            return null;
        }

        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);
        return Carbon::parse($lorryReceipt->advance_on)->translatedFormat($dateFormat);
    }

    public function getFormattedFinalBalanceOnAttribute(): ?string
    {
        $lorryReceipt = $this->matchingLorryReceipt();
        if (!$lorryReceipt || empty($lorryReceipt->final_balance_on)) {
            return null;
        }

        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);
        return Carbon::parse($lorryReceipt->final_balance_on)->translatedFormat($dateFormat);
    }





    private function lorryReceiptSectionCBalance(LorryReceipt $lorryReceipt): int|float|null
    {
        $balanceAmount = self::numericTransportAmount($lorryReceipt->balance_amount);

        if ($balanceAmount > 0) {
            return $balanceAmount;
        }

        $grossHireAmount = self::numericTransportAmount($lorryReceipt->gross_hire_amount);

        if ($grossHireAmount <= 0) {
            $grossHireAmount = self::numericTransportAmount($lorryReceipt->lorry_hire_amount)
                + self::numericTransportAmount($lorryReceipt->other_charges_amount);
        }

        if ($grossHireAmount <= 0) {
            return null;
        }

        return $grossHireAmount - self::numericTransportAmount($lorryReceipt->advance_amount);
    }

    public static function lorryReceiptHasFinalPaymentOperation(LorryReceipt $lorryReceipt): bool
    {
        return collect([
            $lorryReceipt->detention_amount,
            $lorryReceipt->extra_hire_amount,
            $lorryReceipt->final_other_amount,
            $lorryReceipt->less_advance_other_branch_amount,
            $lorryReceipt->less_deduction_claims_amount,
            $lorryReceipt->final_paid_to,
            $lorryReceipt->final_balance_paid_at,
            $lorryReceipt->final_balance_code,
            $lorryReceipt->final_balance_on,
            $lorryReceipt->net_amount_payable,
            $lorryReceipt->final_cash_cheque_no,
            $lorryReceipt->final_cash_cheque_on,
            $lorryReceipt->final_bank,
            $lorryReceipt->final_rupees_only,
            $lorryReceipt->final_passed_by,
            $lorryReceipt->final_certified_by,
            $lorryReceipt->final_prepared_by,
            $lorryReceipt->final_payment_received_by,
        ])->contains(function ($value): bool {
            if ($value === null) {
                return false;
            }
            $trimmed = trim((string) $value);
            return $trimmed !== '' && $trimmed !== '0' && $trimmed !== '0.00';
        });
    }

    private function customFieldsHaveFinalPaymentOperation(): bool
    {
        return collect([
            $this->customFieldAmount(['Add Detention Rs.', 'Detention Amount']),
            $this->customFieldAmount(['Extra Hire Rs', 'Extra Hire Amount']),
            $this->customFieldAmount(['Other Rs', 'Final Other Amount']),
            $this->customFieldAmount(['Less Adv. at other branch', 'Less Advance Other Branch Amount']),
            $this->customFieldAmount(['Less Deduction for Claims', 'Less Deduction Claims Amount']),
            $this->customFieldAmount(['Net Amount Payable']),
            $this->customFieldValue(['Final Paid To']),
            $this->customFieldValue(['Final Balance Amount Paid at', 'Final Balance Code']),
            $this->customFieldValue(['Final Balance Date']),
            $this->customFieldValue(['Cash/Cheque No.', 'Final Cash Cheque No']),
            $this->customFieldValue(['Final Bank']),
            $this->customFieldValue(['Final Rupees Only']),
            $this->customFieldValue(['Final Passed By']),
            $this->customFieldValue(['Final Certified By']),
            $this->customFieldValue(['Final Prepared By']),
            $this->customFieldValue(['Final Payment Received By']),
        ])->contains(function ($value): bool {
            if ($value === null) {
                return false;
            }
            $trimmed = trim((string) $value);
            return $trimmed !== '' && $trimmed !== '0' && $trimmed !== '0.00';
        });
    }

    /**
     * @param  array<int, string>  $labels
     */
    private function customFieldValue(array $labels): ?string
    {
        if (! $this->relationLoaded('fields')) {
            return null;
        }

        $normalizedLabels = collect($labels)
            ->map(fn (string $label): string => $this->normalizeTransportLabel($label))
            ->all();

        foreach ($this->fields as $field) {
            $customField = $field->customField;

            if (! $customField) {
                continue;
            }

            $matches = in_array($this->normalizeTransportLabel($customField->label), $normalizedLabels, true)
                || in_array($this->normalizeTransportLabel($customField->name), $normalizedLabels, true);

            if (! $matches) {
                continue;
            }

            return $field->defaultAnswer;
        }

        return null;
    }

    private function customFieldFinalNetAmountPayable(): int|float|null
    {
        $netAmountPayable = $this->customFieldAmount(['Net Amount Payable']);

        if ($netAmountPayable !== null) {
            return $netAmountPayable;
        }

        $balancePayable = $this->customFieldSectionCBalance();

        if ($balancePayable === null) {
            return null;
        }

        $extraTotal = $this->sumTransportAmounts([
            $this->customFieldAmount(['Add Detention Rs.', 'Detention Amount']),
            $this->customFieldAmount(['Extra Hire Rs', 'Extra Hire Amount']),
            $this->customFieldAmount(['Other Rs', 'Final Other Amount']),
        ]) ?? 0;

        $deductionTotal = $this->sumTransportAmounts([
            $this->customFieldAmount(['Less Adv. at other branch', 'Less Advance Other Branch Amount']),
            $this->customFieldAmount(['Less Deduction for Claims', 'Less Deduction Claims Amount']),
        ]) ?? 0;

        return $balancePayable + $extraTotal - $deductionTotal;
    }

    private function customFieldSectionCBalance(): int|float|null
    {
        $balanceAmount = $this->customFieldAmount(['Balance Amount', 'Balance Rupees']);

        if ($balanceAmount !== null && $balanceAmount > 0) {
            return $balanceAmount;
        }

        $grossHireAmount = $this->sumTransportAmounts([
            $this->customFieldAmount(['Lorry Hire', 'Lorry Hire Amount']),
            $this->customFieldAmount(['Add Other Charges', 'Other Charges Amount']),
        ]) ?? $this->customFieldAmount(['Gross Hire Rupees', 'Gross Hire Amount']);

        if ($grossHireAmount === null || $grossHireAmount <= 0) {
            return null;
        }

        return $grossHireAmount - ($this->customFieldAmount(['Advance Paid Rs', 'Advance Amount']) ?? 0);
    }

    /**
     * @param  array<int, int|float|null>  $amounts
     */
    private function sumTransportAmounts(array $amounts): int|float|null
    {
        $amounts = collect($amounts)->filter(fn ($amount): bool => $amount !== null);

        if ($amounts->isEmpty()) {
            return null;
        }

        return $amounts->sum();
    }

    /**
     * @param  array<int, string>  $labels
     */
    private function customFieldAmount(array $labels): int|float|null
    {
        if (! $this->relationLoaded('fields')) {
            return null;
        }

        $normalizedLabels = collect($labels)
            ->map(fn (string $label): string => $this->normalizeTransportLabel($label))
            ->all();

        foreach ($this->fields as $field) {
            $customField = $field->customField;

            if (! $customField) {
                continue;
            }

            $matches = in_array($this->normalizeTransportLabel($customField->label), $normalizedLabels, true)
                || in_array($this->normalizeTransportLabel($customField->name), $normalizedLabels, true);

            if (! $matches) {
                continue;
            }

            $amount = $this->nullableTransportAmount($field->defaultAnswer);

            if ($amount !== null) {
                return $amount;
            }
        }

        return null;
    }

    private function nullableTransportAmount(mixed $amount): int|float|null
    {
        if ($amount === null || trim((string) $amount) === '') {
            return null;
        }

        $number = str_replace(',', '', (string) $amount);

        if (! is_numeric($number)) {
            return null;
        }

        $numericAmount = (float) $number;

        return (float) (int) $numericAmount === $numericAmount ? (int) $numericAmount : $numericAmount;
    }

    private function normalizeTransportLabel(?string $label): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $label));
    }

    public function getMatchingLorryReceiptInvoiceId(): ?int
    {
        if ($this->template_name !== self::TEMPLATE_LR_RECEIPT || empty($this->invoice_number)) {
            return null;
        }

        $lorryReceipt = LorryReceipt::query()
            ->where('company_id', $this->company_id)
            ->where('received_no_bilties', $this->invoice_number)
            ->first();

        if (! $lorryReceipt) {
            return null;
        }

        $invoice = Invoice::query()
            ->where('company_id', $this->company_id)
            ->where('template_name', self::TEMPLATE_LORRY_RECEIPT)
            ->where('invoice_number', $lorryReceipt->challan_no)
            ->first();

        return $invoice?->id;
    }

    public static function numericTransportAmount(mixed $amount): int|float
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        $number = str_replace(',', '', (string) $amount);

        if (! is_numeric($number)) {
            return 0;
        }

        $numericAmount = (float) $number;

        return (float) (int) $numericAmount === $numericAmount ? (int) $numericAmount : $numericAmount;
    }

    public function determineLorryReceiptStatus(): string
    {
        if ($this->template_name !== self::TEMPLATE_LORRY_RECEIPT) {
            return $this->status;
        }

        $lorryReceipt = $this->matchingLorryReceipt();
        if (!$lorryReceipt) {
            return $this->status;
        }

        // 1. Get all dockets (LR numbers) inside this Lorry Receipt
        $dockets = array_map('trim', explode(',', $lorryReceipt->received_no_bilties));
        $dockets = array_unique(array_filter($dockets));

        // 2. Check if Profit & Loss is calculated for EACH LR docket
        $isCompleted = false;
        if (!empty($dockets)) {
            $lrReceipts = self::where('company_id', $this->company_id)
                ->where('template_name', self::TEMPLATE_LR_RECEIPT)
                ->whereIn('invoice_number', $dockets)
                ->get();

            $allCalculated = true;
            foreach ($dockets as $docket) {
                $match = $lrReceipts->firstWhere('invoice_number', $docket);
                if (!$match || (int) $match->lorry_receipt_id !== (int) $lorryReceipt->id) {
                    $allCalculated = false;
                    break;
                }
            }
            if ($allCalculated) {
                $isCompleted = true;
            }
        }

        // 3. Determine status
        if ($isCompleted) {
            $status = self::STATUS_COMPLETED;
        } else {
            // Check if Section E is filled
            $hasFinal = self::lorryReceiptHasFinalPaymentOperation($lorryReceipt);
            if ($hasFinal) {
                $status = 'PAID';
            } else {
                // Check if Section C advance paid value is there (advance_amount > 0)
                $advanceAmount = self::numericTransportAmount($lorryReceipt->advance_amount);
                if ($advanceAmount > 0) {
                    $status = 'IN PROGRESS';
                } else {
                    $status = self::STATUS_DRAFT;
                }
            }
        }

        $this->status = $status;
        return $status;
    }

    public function updateLorryReceiptStatus(): void
    {
        if ($this->template_name !== self::TEMPLATE_LORRY_RECEIPT) {
            return;
        }

        $oldStatus = $this->status;
        $newStatus = $this->determineLorryReceiptStatus();

        if ($oldStatus !== $newStatus) {
            $this->saveQuietly();
        }
    }
}
