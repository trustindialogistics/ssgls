<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        $pod = $this->getFirstMedia('pod');
        $lorryDocuments = collect(Invoice::LORRY_DOCUMENT_COLLECTIONS)
            ->mapWithKeys(function ($label, $collection) {
                $media = $this->getFirstMedia($collection);

                return [
                    $collection => $media ? [
                        'label' => $label,
                        'file_name' => $media->file_name,
                        'mime_type' => $media->mime_type,
                        'size' => $media->size,
                    ] : null,
                ];
            });

        return [
            'id' => $this->id,
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'invoice_number' => $this->invoice_number,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'paid_status' => $this->paid_status,
            'tax_per_item' => $this->tax_per_item,
            'tax_included' => $this->tax_included,
            'discount_per_item' => $this->discount_per_item,
            'notes' => $this->notes,
            'discount_type' => $this->discount_type,
            'discount' => $this->discount,
            'discount_val' => $this->discount_val,
            'sub_total' => $this->sub_total,
            'total' => $this->total,
            'tax' => $this->tax,
            'due_amount' => $this->due_amount,
            'display_due_amount' => $this->displayDueAmount(),
            'sent' => $this->sent,
            'viewed' => $this->viewed,
            'unique_hash' => $this->unique_hash,
            'template_name' => $this->template_name,
            'customer_id' => $this->customer_id,
            'recurring_invoice_id' => $this->recurring_invoice_id,
            'sequence_number' => $this->sequence_number,
            'exchange_rate' => $this->exchange_rate,
            'base_discount_val' => $this->base_discount_val,
            'base_sub_total' => $this->base_sub_total,
            'base_total' => $this->base_total,
            'creator_id' => $this->creator_id,
            'base_tax' => $this->base_tax,
            'base_due_amount' => $this->base_due_amount,
            'currency_id' => $this->currency_id,
            'amount_debit' => $this->template_name === Invoice::TEMPLATE_LR_RECEIPT
                ? $this->amountDebit * 100
                : $this->amountDebit,
            'amount_credit' => $this->amountCredit,
            'amount_paid' => $this->amountPaid,
            'lorry_receipt_advance_amount' => $this->template_name === Invoice::TEMPLATE_LORRY_RECEIPT
                ? $this->lorryReceiptAdvanceAmount * 100
                : $this->lorryReceiptAdvanceAmount,
            'lorry_receipt_display_net_amount' => $this->template_name === Invoice::TEMPLATE_LORRY_RECEIPT
                ? ($this->lorryReceiptDisplayNetAmount !== null ? $this->lorryReceiptDisplayNetAmount * 100 : null)
                : $this->lorryReceiptDisplayNetAmount,
            'formatted_created_at' => $this->formattedCreatedAt,
            'invoice_pdf_url' => $this->invoicePdfUrl,
            'pod_url' => $pod ? url('/reports/invoices/'.$this->id.'/pod') : null,
            'pod_meta' => $pod ? [
                'uuid' => $pod->uuid,
                'file_name' => $pod->file_name,
                'mime_type' => $pod->mime_type,
            ] : null,
            'lorry_documents' => $lorryDocuments,
            'formatted_invoice_date' => $this->formattedInvoiceDate,
            'formatted_due_date' => $this->formattedDueDate,
            'allow_edit' => $this->allow_edit,
            'payment_module_enabled' => $this->payment_module_enabled,
            'sales_tax_type' => $this->sales_tax_type,
            'sales_tax_address_type' => $this->sales_tax_address_type,
            'overdue' => $this->overdue,
            'items' => $this->whenLoaded('items', function () {
                return InvoiceItemResource::collection($this->items);
            }),
            'customer' => $this->when($this->relationLoaded('customer') && $this->customer, function () {
                return new CustomerResource($this->customer);
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return new UserResource($this->creator);
            }),
            'taxes' => $this->whenLoaded('taxes', function () {
                return TaxResource::collection($this->taxes);
            }),
            'fields' => $this->whenLoaded('fields', function () {
                return CustomFieldValueResource::collection($this->fields);
            }),
            'company' => $this->whenLoaded('company', function () {
                return new CompanyResource($this->company);
            }),
            'currency' => $this->whenLoaded('currency', function () {
                return new CurrencyResource($this->currency);
            }),
        ];
    }

    private function displayDueAmount(): int|float|null
    {
        if ($this->template_name !== Invoice::TEMPLATE_LORRY_RECEIPT) {
            return $this->due_amount;
        }

        $amount = $this->lorryReceiptDisplayNetAmount
            ?? ($this->relationLoaded('fields') ? $this->lorryReceiptFinalAmountPayable() : null)
            ?? $this->due_amount;

        return $amount !== null ? $amount * 100 : null;
    }

    private function lorryReceiptFinalAmountPayable(): int|float|null
    {
        $netAmount = $this->amount([
            'Net Amount Payable',
        ]);

        if ($netAmount !== null && $netAmount > 0) {
            return $netAmount;
        }

        $detentionAmount = $this->amount(['Add Detention Rs.', 'Detention Amount']);
        $extraHireAmount = $this->amount(['Extra Hire Rs', 'Extra Hire Amount']);
        $finalOtherAmount = $this->amount(['Other Rs', 'Final Other Amount']);
        $lessAdvanceOtherBranchAmount = $this->amount(['Less Adv. at other branch', 'Less Advance Other Branch Amount']);
        $lessDeductionClaimsAmount = $this->amount(['Less Deduction for Claims', 'Less Deduction Claims Amount']);
        $hasFinalPaymentOperation = collect([
            $detentionAmount,
            $extraHireAmount,
            $finalOtherAmount,
            $lessAdvanceOtherBranchAmount,
            $lessDeductionClaimsAmount,
        ])->contains(fn ($amount): bool => $amount !== null);

        if (! $hasFinalPaymentOperation) {
            return null;
        }

        $grossHire = $this->sumAmounts([
            $this->amount(['Lorry Hire', 'Lorry Hire Amount']),
            $this->amount(['Add Other Charges', 'Other Charges Amount']),
        ]) ?? $this->amount(['Gross Hire Rupees', 'Gross Hire Amount']);

        $balancePayable = $grossHire !== null
            ? $grossHire - ($this->amount(['Advance Paid Rs', 'Advance Amount']) ?? 0)
            : $this->amount(['Balance Amount', 'Balance Rupees']);

        $extraTotal = $this->sumAmounts([
            $detentionAmount,
            $extraHireAmount,
            $finalOtherAmount,
        ]) ?? $this->amount(['Final Total Extra Amount']);

        $grandTotal = $this->sumAmounts([$balancePayable, $extraTotal])
            ?? $this->amount(['Grand Total']);

        if ($grandTotal === null) {
            return null;
        }

        $deductionTotal = $this->sumAmounts([
            $lessAdvanceOtherBranchAmount,
            $lessDeductionClaimsAmount,
        ]) ?? $this->amount(['Total Less Amount']);

        return $grandTotal - ($deductionTotal ?? 0);
    }

    /**
     * @param  array<int, int|float|null>  $values
     */
    private function sumAmounts(array $values): int|float|null
    {
        $values = collect($values)->filter(fn ($value): bool => $value !== null);

        if ($values->isEmpty()) {
            return null;
        }

        return $values->sum();
    }

    /**
     * @param  string|array<int, string>  $labels
     */
    private function amount(string|array $labels): int|float|null
    {
        $value = trim((string) $this->fieldValue($labels));

        if ($value === '') {
            return null;
        }

        $number = str_replace(',', '', $value);

        if (! is_numeric($number)) {
            return null;
        }

        return (float) $number;
    }

    /**
     * @param  string|array<int, string>  $labels
     */
    private function fieldValue(string|array $labels): mixed
    {
        $normalizedLabels = collect((array) $labels)
            ->map(fn (string $label): string => $this->normalizeLabel($label))
            ->all();

        $matchedValue = null;

        foreach ($this->fields as $field) {
            $customField = $field->customField;

            if (! $customField) {
                continue;
            }

            $matches = in_array($this->normalizeLabel($customField->label), $normalizedLabels, true)
                || in_array($this->normalizeLabel($customField->name), $normalizedLabels, true);

            if (! $matches) {
                continue;
            }

            if (trim((string) $field->defaultAnswer) !== '') {
                return $field->defaultAnswer;
            }

            $matchedValue = $field->defaultAnswer;
        }

        return $matchedValue;
    }

    private function normalizeLabel(?string $label): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $label));
    }
}
