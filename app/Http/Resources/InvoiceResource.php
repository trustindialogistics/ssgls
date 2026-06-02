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
            'customer' => $this->whenLoaded('customer', function () {
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
}
