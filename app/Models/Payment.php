<?php

namespace App\Models;

use App\Facades\Hashids;
use App\Jobs\GeneratePaymentPdfJob;
use App\Mail\SendPaymentMail;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\SerialNumberFormatter;
use App\Traits\GeneratesPdfTrait;
use App\Traits\HasCustomFieldsTrait;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Payment extends Model implements HasMedia
{
    use GeneratesPdfTrait;
    use HasCustomFieldsTrait;
    use HasFactory;
    use InteractsWithMedia;

    public const PAYMENT_MODE_CHECK = 'CHECK';

    public const PAYMENT_MODE_OTHER = 'OTHER';

    public const PAYMENT_MODE_CASH = 'CASH';

    public const PAYMENT_MODE_CREDIT_CARD = 'CREDIT_CARD';

    public const PAYMENT_MODE_BANK_TRANSFER = 'BANK_TRANSFER';

    protected $dates = ['created_at', 'updated_at', 'payment_date'];

    protected $guarded = ['id'];

    protected $appends = [
        'formattedCreatedAt',
        'formattedPaymentDate',
        'paymentPdfUrl',
    ];

    protected function casts(): array
    {
        return [
            'notes' => 'string',
            'exchange_rate' => 'float',
        ];
    }

    protected static function booted()
    {
        static::created(function ($payment) {
            GeneratePaymentPdfJob::dispatch($payment);
        });

        static::updated(function ($payment) {
            GeneratePaymentPdfJob::dispatch($payment, true);
        });
    }

    public function setSettingsAttribute($value)
    {
        if ($value) {
            $this->attributes['settings'] = json_encode($value);
        }
    }

    public function getFormattedCreatedAtAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->created_at)->translatedFormat($dateFormat);
    }

    public function getFormattedPaymentDateAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->payment_date)->translatedFormat($dateFormat);
    }

    public function getPaymentPdfUrlAttribute()
    {
        return url('/payments/pdf/'.$this->unique_hash);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function emailLogs(): MorphMany
    {
        return $this->morphMany('App\Models\EmailLog', 'mailable');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function sendPaymentData($data)
    {
        $data['payment'] = $this->toArray();
        $data['user'] = $this->customer->toArray();
        $data['company'] = Company::find($this->company_id);
        $data['body'] = $this->getEmailBody($data['body']);
        $data['attach']['data'] = $this->getPDFData();

        return $data;
    }

    public function send($data)
    {
        $data = $this->sendPaymentData($data);

        $mail = \Mail::to($data['to']);
        if (! empty($data['cc'])) {
            $mail->cc($data['cc']);
        }
        if (! empty($data['bcc'])) {
            $mail->bcc($data['bcc']);
        }
        $mail->send(new SendPaymentMail($data));

        return [
            'success' => true,
        ];
    }

    public static function createPayment($request)
    {
        $data = $request->getPaymentPayload();

        if ($request->invoice_id) {
            $invoice = Invoice::find($request->invoice_id);
            self::subtractInvoiceSettlement(
                $invoice,
                self::getSettlementAmountFromRequest($request),
                $data['invoice_paid_status'] ?? null
            );
        }

        $payment = Payment::create($data);
        $payment->unique_hash = Hashids::connection(Payment::class)->encode($payment->id);

        $serial = (new SerialNumberFormatter)
            ->setModel($payment)
            ->setCompany($payment->company_id)
            ->setCustomer($payment->customer_id)
            ->setNextNumbers();

        $payment->sequence_number = $serial->nextSequenceNumber;
        $payment->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $payment->save();

        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));

        if ((string) $payment['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($payment);
        }

        $customFields = $request->customFields;

        if ($customFields) {
            $payment->addCustomFields($customFields);
        }

        $payment->syncDeductionExpense();

        $payment = Payment::with([
            'customer',
            'invoice',
            'paymentMethod',
            'fields',
        ])->find($payment->id);

        return $payment;
    }

    public function updatePayment($request)
    {
        $data = $request->getPaymentPayload();
        $oldSettlementAmount = $this->getSettlementAmount();
        $newSettlementAmount = self::getSettlementAmountFromRequest($request);
        $newPaidStatus = $data['invoice_paid_status'] ?? null;

        if ($request->invoice_id && (! $this->invoice_id || $this->invoice_id !== $request->invoice_id)) {
            $invoice = Invoice::find($request->invoice_id);
            self::subtractInvoiceSettlement($invoice, $newSettlementAmount, $newPaidStatus);
        }

        if ($this->invoice_id && (! $request->invoice_id || $this->invoice_id !== $request->invoice_id)) {
            $invoice = Invoice::find($this->invoice_id);
            $invoice->addInvoicePayment($oldSettlementAmount);
        }

        if (
            $this->invoice_id &&
            $this->invoice_id === $request->invoice_id &&
            (
                $newSettlementAmount !== $oldSettlementAmount ||
                ($this->invoice_paid_status ?? null) !== $newPaidStatus
            )
        ) {
            $invoice = Invoice::find($this->invoice_id);
            $invoice->addInvoicePayment($oldSettlementAmount);
            self::subtractInvoiceSettlement($invoice, $newSettlementAmount, $newPaidStatus);
        }

        $serial = (new SerialNumberFormatter)
            ->setModel($this)
            ->setCompany($this->company_id)
            ->setCustomer($request->customer_id)
            ->setModelObject($this->id)
            ->setNextNumbers();

        $data['customer_sequence_number'] = $serial->nextCustomerSequenceNumber;
        $this->update($data);

        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));

        if ((string) $data['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($this);
        }

        $customFields = $request->customFields;

        if ($customFields) {
            $this->updateCustomFields($customFields);
        }

        $this->refresh()->syncDeductionExpense();

        $payment = Payment::with([
            'customer',
            'invoice',
            'paymentMethod',
        ])
            ->find($this->id);

        return $payment;
    }

    public static function deletePayments($ids)
    {
        foreach ($ids as $id) {
            $payment = Payment::find($id);

            if (! $payment) {
                continue;
            }

            $payment->deleteDeductionExpense();

            if ($payment->invoice_id != null) {
                $invoice = Invoice::find($payment->invoice_id);
                $invoice->due_amount = ((int) $invoice->due_amount + $payment->getSettlementAmount());

                if ($invoice->due_amount == $invoice->total) {
                    $invoice->paid_status = Invoice::STATUS_UNPAID;
                } else {
                    $invoice->paid_status = Invoice::STATUS_PARTIALLY_PAID;
                }

                $invoice->status = $invoice->getPreviousStatus();
                $invoice->save();
            }

            $payment->delete();
        }

        return true;
    }

    public function getSettlementAmount(): int
    {
        return (int) $this->amount + (int) $this->tds_amount + (int) $this->deduction_amount;
    }

    protected static function getSettlementAmountFromRequest($request): int
    {
        return (int) $request->amount + (int) $request->tds_amount + (int) $request->deduction_amount;
    }

    protected static function subtractInvoiceSettlement(Invoice $invoice, int $amount, ?string $paidStatus = null): void
    {
        $invoice->subtractInvoicePayment($amount);

        if ($paidStatus) {
            self::applyInvoicePaidStatus($invoice, $paidStatus);
        }
    }

    protected static function applyInvoicePaidStatus(Invoice $invoice, string $paidStatus): void
    {
        if ($paidStatus === Invoice::STATUS_PAID) {
            $invoice->due_amount = 0;
            $invoice->base_due_amount = 0;
            $invoice->paid_status = Invoice::STATUS_PAID;
            $invoice->status = Invoice::STATUS_COMPLETED;
            $invoice->overdue = false;
        } elseif ($paidStatus === Invoice::STATUS_UNPAID) {
            $invoice->due_amount = $invoice->total;
            $invoice->base_due_amount = $invoice->due_amount * $invoice->exchange_rate;
            $invoice->paid_status = Invoice::STATUS_UNPAID;
            $invoice->status = $invoice->getPreviousStatus();
        } elseif ($paidStatus === Invoice::STATUS_PARTIALLY_PAID) {
            $invoice->due_amount = max(0, $invoice->due_amount);
            $invoice->base_due_amount = $invoice->due_amount * $invoice->exchange_rate;
            $invoice->paid_status = Invoice::STATUS_PARTIALLY_PAID;
            $invoice->status = $invoice->getPreviousStatus();
        }

        $invoice->save();
    }

    public function syncDeductionExpense(): void
    {
        if (! $this->invoice_id) {
            $this->deleteDeductionExpense();

            return;
        }

        $deductionAmount = (int) $this->tds_amount + (int) $this->deduction_amount;

        if ($deductionAmount <= 0) {
            $this->deleteDeductionExpense();

            return;
        }

        $invoice = $this->invoice ?: Invoice::find($this->invoice_id);
        $category = ExpenseCategory::firstOrCreate(
            [
                'company_id' => $this->company_id,
                'name' => 'Payment Deductions',
            ],
            [
                'description' => 'Auto-created expenses for TDS and customer deductions recorded during payment entry.',
            ]
        );

        Expense::updateOrCreate(
            [
                'payment_id' => $this->id,
                'auto_generated' => true,
            ],
            [
                'expense_date' => $this->payment_date,
                'expense_number' => 'AUTO-PAY-'.$this->payment_number,
                'amount' => $deductionAmount,
                'notes' => $this->getDeductionExpenseNotes($invoice),
                'expense_category_id' => $category->id,
                'company_id' => $this->company_id,
                'customer_id' => $this->customer_id,
                'creator_id' => $this->creator_id,
                'exchange_rate' => $this->exchange_rate,
                'base_amount' => $deductionAmount * $this->exchange_rate,
                'currency_id' => $this->currency_id,
                'payment_method_id' => $this->payment_method_id,
                'invoice_id' => $this->invoice_id,
            ]
        );
    }

    public function deleteDeductionExpense(): void
    {
        Expense::where('payment_id', $this->id)
            ->where('auto_generated', true)
            ->delete();
    }

    protected function getDeductionExpenseNotes(?Invoice $invoice): string
    {
        $parts = [];

        if ((int) $this->tds_amount > 0) {
            $parts[] = 'TDS: '.($this->tds_amount / 100);
        }

        if ((int) $this->deduction_amount > 0) {
            $parts[] = 'Deduction: '.($this->deduction_amount / 100);
        }

        $invoiceNumber = $invoice?->invoice_number ?: $this->invoice_id;

        return 'Auto expense for payment '.$this->payment_number.' against invoice '.$invoiceNumber.'. '.implode(', ', $parts);
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

    public function scopePaymentNumber($query, $paymentNumber)
    {
        return $query->where('payments.payment_number', 'LIKE', '%'.$paymentNumber.'%');
    }

    public function scopePaymentMethod($query, $paymentMethodId)
    {
        return $query->where('payments.payment_method_id', $paymentMethodId);
    }

    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('payment_number')) {
            $query->paymentNumber($filters->get('payment_number'));
        }

        if ($filters->get('payment_id')) {
            $query->wherePayment($filters->get('payment_id'));
        }

        if ($filters->get('payment_method_id')) {
            $query->paymentMethod($filters->get('payment_method_id'));
        }

        if ($filters->get('customer_id')) {
            $query->whereCustomer($filters->get('customer_id'));
        }

        if ($filters->get('from_date') && $filters->get('to_date')) {
            $start = Carbon::createFromFormat('Y-m-d', $filters->get('from_date'));
            $end = Carbon::createFromFormat('Y-m-d', $filters->get('to_date'));
            $query->paymentsBetween($start, $end);
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'sequence_number';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'desc';
            $query->whereOrder($field, $orderBy);
        }
    }

    public function scopePaymentsBetween($query, $start, $end)
    {
        return $query->whereBetween(
            'payments.payment_date',
            [$start->format('Y-m-d'), $end->format('Y-m-d')]
        );
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy)
    {
        $query->orderBy($orderByField, $orderBy);
    }

    public function scopeWherePayment($query, $payment_id)
    {
        $query->orWhere('id', $payment_id);
    }

    public function scopeWhereCompany($query)
    {
        $query->where('payments.company_id', request()->header('company'));
    }

    public function scopeWhereCustomer($query, $customer_id)
    {
        $query->where('payments.customer_id', $customer_id);
    }

    public function getPDFData()
    {
        $company = Company::find($this->company_id);
        $locale = CompanySetting::getSetting('language', $company->id);

        \App::setLocale($locale);

        $logo = $company->logo_path;

        view()->share([
            'payment' => $this,
            'company_address' => $this->getCompanyAddress(),
            'billing_address' => $this->getCustomerBillingAddress(),
            'notes' => $this->getNotes(),
            'logo' => $logo ?? null,
        ]);

        if (request()->has('preview')) {
            return view('app.pdf.payment.payment');
        }

        return PDF::loadView('app.pdf.payment.payment');
    }

    public function getCompanyAddress()
    {
        if ($this->company && (! $this->company->address()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('payment_company_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    public function getCustomerBillingAddress()
    {
        if ($this->customer && (! $this->customer->billingAddress()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('payment_from_customer_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    public function getEmailAttachmentSetting()
    {
        return true;
    }

    public function getNotes()
    {
        return $this->getFormattedString($this->notes);
    }

    public function getEmailBody($body)
    {
        $values = array_merge($this->getFieldsArray(), $this->getExtraFields());

        $body = strtr($body, $values);

        return preg_replace('/{(.*?)}/', '', $body);
    }

    public function getExtraFields()
    {
        return [
            '{PAYMENT_DATE}' => $this->formattedPaymentDate,
            '{PAYMENT_MODE}' => $this->paymentMethod ? $this->paymentMethod->name : null,
            '{PAYMENT_NUMBER}' => $this->payment_number,
            '{PAYMENT_AMOUNT}' => format_money_pdf($this->amount, $this->customer->currency),
        ];
    }

    public static function generatePayment($transaction)
    {
        $invoice = Invoice::find($transaction->invoice_id);

        $serial = (new SerialNumberFormatter)
            ->setModel(new Payment)
            ->setCompany($invoice->company_id)
            ->setCustomer($invoice->customer_id)
            ->setNextNumbers();

        $data['payment_number'] = $serial->getNextNumber();
        $data['payment_date'] = Carbon::now();
        $data['amount'] = $invoice->total;
        $data['invoice_id'] = $invoice->id;
        $data['payment_method_id'] = request()->payment_method_id;
        $data['customer_id'] = $invoice->customer_id;
        $data['exchange_rate'] = $invoice->exchange_rate;
        $data['base_amount'] = $data['amount'] * $data['exchange_rate'];
        $data['currency_id'] = $invoice->currency_id;
        $data['company_id'] = $invoice->company_id;
        $data['transaction_id'] = $transaction->id;

        $payment = Payment::create($data);
        $payment->unique_hash = Hashids::connection(Payment::class)->encode($payment->id);
        $payment->sequence_number = $serial->nextSequenceNumber;
        $payment->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $payment->save();

        $invoice->subtractInvoicePayment($invoice->total);

        return $payment;
    }
}
